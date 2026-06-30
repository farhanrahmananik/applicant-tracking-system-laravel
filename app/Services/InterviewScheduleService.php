<?php

namespace App\Services;

use App\Models\Application;
use App\Models\InterviewSchedule;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewScheduleService
{
    private const INTERVIEWER_ROLE_SLUGS = [
        'super_admin',
        'hr_manager',
        'recruiter',
        'interviewer',
    ];

    public function __construct(
        private readonly EmailNotificationService $emailNotificationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<InterviewSchedule>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = $this->allowedFilter($filters['type'] ?? null, InterviewSchedule::TYPES);
        $status = $this->allowedFilter($filters['status'] ?? null, InterviewSchedule::STATUSES);
        $dateFrom = $this->validDate($filters['date_from'] ?? null);
        $dateTo = $this->validDate($filters['date_to'] ?? null);
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $now = now();

        return InterviewSchedule::query()
            ->with([
                'application:id,candidate_id,job_posting_id,current_status,deleted_at',
                'application.candidate:id,first_name,last_name,email,deleted_at',
                'application.jobPosting:id,company_id,title,status,deleted_at',
                'application.jobPosting.company:id,name,deleted_at',
                'interviewer:id,name,email',
            ])
            ->when($terms !== [], function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function ($query) use ($term): void {
                        $query
                            ->whereHas('application.candidate', function ($query) use ($term): void {
                                $query
                                    ->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('last_name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            })
                            ->orWhereHas(
                                'application.jobPosting',
                                fn ($query) => $query->where('title', 'like', "%{$term}%"),
                            )
                            ->orWhereHas('interviewer', function ($query) use ($term): void {
                                $query
                                    ->where('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            });
                    });
                }
            })
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== null, fn ($query) => $query->whereDate('scheduled_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($query) => $query->whereDate('scheduled_at', '<=', $dateTo))
            ->orderByRaw('CASE WHEN scheduled_at >= ? THEN 0 ELSE 1 END', [$now])
            ->orderByRaw('CASE WHEN scheduled_at >= ? THEN scheduled_at END ASC', [$now])
            ->orderByRaw('CASE WHEN scheduled_at < ? THEN scheduled_at END DESC', [$now])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Application>
     */
    public function applicationOptions(?int $includeApplicationId = null): Collection
    {
        return Application::query()
            ->select(['id', 'candidate_id', 'job_posting_id', 'current_status'])
            ->with([
                'candidate:id,first_name,last_name,email',
                'jobPosting:id,company_id,title',
                'jobPosting.company:id,name',
            ])
            ->where(function ($query) use ($includeApplicationId): void {
                $query
                    ->whereIn('current_status', Application::ACTIVE_STATUSES)
                    ->when(
                        $includeApplicationId !== null,
                        fn ($query) => $query->orWhere('applications.id', $includeApplicationId),
                    );
            })
            ->latest('applied_date')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function interviewerOptions(?int $includeUserId = null): Collection
    {
        return User::query()
            ->select(['id', 'name', 'email', 'is_active'])
            ->where(function ($query) use ($includeUserId): void {
                $query
                    ->where(function ($query): void {
                        $query
                            ->where('is_active', true)
                            ->whereHas(
                                'roles',
                                fn ($query) => $query->whereIn('slug', self::INTERVIEWER_ROLE_SLUGS),
                            );
                    })
                    ->when(
                        $includeUserId !== null,
                        fn ($query) => $query->orWhere('users.id', $includeUserId),
                    );
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InterviewSchedule
    {
        $interview = DB::transaction(function () use ($data): InterviewSchedule {
            $this->ensureApplicationCanBeScheduled((int) $data['application_id']);
            $this->ensureInterviewerIsEligible((int) $data['interviewer_id']);

            $actorId = Auth::id();
            $data['created_by_id'] = $actorId;
            $data['updated_by_id'] = $actorId;

            $interview = InterviewSchedule::query()->create($data)->load([
                'application.candidate',
                'application.jobPosting.company',
                'interviewer',
                'createdBy',
                'updatedBy',
            ]);
            $this->auditLogService->created(
                $interview,
                "Interview #{$interview->getKey()} scheduled.",
            );

            return $interview;
        }, 3);

        if ($interview->status === 'cancelled') {
            $this->emailNotificationService->interviewCancelled($interview);
        } else {
            $this->emailNotificationService->interviewScheduled($interview);
        }

        return $interview;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InterviewSchedule $interview, array $data): InterviewSchedule
    {
        $previousStatus = $interview->status;
        $interview = DB::transaction(function () use ($interview, $data): InterviewSchedule {
            $before = $this->auditLogService->snapshot($interview);
            $this->ensureApplicationCanBeScheduled((int) $data['application_id']);
            $this->ensureInterviewerIsEligible((int) $data['interviewer_id']);

            $data['updated_by_id'] = Auth::id();
            $interview->update($data);

            if (($before['status'] ?? null) !== $interview->status) {
                $this->auditLogService->statusChanged(
                    $interview,
                    'status',
                    (string) ($before['status'] ?? ''),
                    $interview->status,
                    $interview->status === 'cancelled'
                        ? "Interview #{$interview->getKey()} cancelled."
                        : "Interview #{$interview->getKey()} status changed.",
                );
            } else {
                $this->auditLogService->updated(
                    $interview,
                    $before,
                    "Interview #{$interview->getKey()} updated.",
                );
            }

            return $interview->refresh()->load([
                'application.candidate',
                'application.jobPosting.company',
                'interviewer',
                'createdBy',
                'updatedBy',
            ]);
        }, 3);

        if ($interview->status === 'cancelled' && $previousStatus !== 'cancelled') {
            $this->emailNotificationService->interviewCancelled($interview);
        } else {
            $this->emailNotificationService->interviewUpdated($interview);
        }

        return $interview;
    }

    public function delete(InterviewSchedule $interview): void
    {
        DB::transaction(function () use ($interview): void {
            $before = $this->auditLogService->snapshot($interview);
            $interview->delete();
            $this->auditLogService->deleted(
                $interview,
                "Interview #{$interview->getKey()} deleted.",
                $before,
            );
        });
    }

    /**
     * @throws ValidationException
     */
    private function ensureApplicationCanBeScheduled(int $applicationId): void
    {
        $application = Application::query()
            ->whereKey($applicationId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($application->isTerminal()) {
            throw ValidationException::withMessages([
                'application_id' => 'Interviews cannot be scheduled for rejected or withdrawn applications.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureInterviewerIsEligible(int $interviewerId): void
    {
        $interviewer = User::query()
            ->whereKey($interviewerId)
            ->where('is_active', true)
            ->whereHas(
                'roles',
                fn ($query) => $query->whereIn('slug', self::INTERVIEWER_ROLE_SLUGS),
            )
            ->lockForUpdate()
            ->first(['id']);

        if ($interviewer === null) {
            throw ValidationException::withMessages([
                'interviewer_id' => 'The selected interviewer must be an active internal ATS user.',
            ]);
        }
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function allowedFilter(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
