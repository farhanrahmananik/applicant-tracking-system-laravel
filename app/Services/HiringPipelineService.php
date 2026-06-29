<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStageHistory;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HiringPipelineService
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'applied' => ['screening', 'shortlisted', 'rejected', 'withdrawn'],
        'screening' => ['shortlisted', 'rejected', 'withdrawn'],
        'shortlisted' => ['interview', 'selected', 'rejected', 'withdrawn'],
        'interview' => ['shortlisted', 'selected', 'rejected', 'withdrawn'],
        'selected' => [],
        'rejected' => [],
        'withdrawn' => [],
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, Collection<int, Application>>
     */
    public function board(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $jobPostingId = $this->positiveInteger($filters['job_posting_id'] ?? null);
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $applications = Application::query()
            ->with([
                'candidate:id,first_name,last_name,email,current_position,deleted_at',
                'jobPosting:id,company_id,title,status,deleted_at',
                'jobPosting.company:id,name,deleted_at',
            ])
            ->when($terms !== [], function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function ($query) use ($term): void {
                        $query
                            ->whereHas('candidate', function ($query) use ($term): void {
                                $query
                                    ->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('last_name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            })
                            ->orWhereHas(
                                'jobPosting',
                                fn ($query) => $query->where('title', 'like', "%{$term}%"),
                            );
                    });
                }
            })
            ->when(
                $jobPostingId !== null,
                fn ($query) => $query->where('job_posting_id', $jobPostingId),
            )
            ->oldest('applied_date')
            ->oldest('id')
            ->get()
            ->groupBy('current_status');

        return collect(Application::PIPELINE_STAGES)
            ->mapWithKeys(fn (string $stage): array => [
                $stage => $applications->get($stage, new Collection),
            ])
            ->all();
    }

    /**
     * @return Collection<int, JobPosting>
     */
    public function jobPostingOptions(): Collection
    {
        return JobPosting::query()
            ->select(['id', 'company_id', 'title'])
            ->with('company:id,name')
            ->whereHas('applications')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function allowedTransitions(string $stage): array
    {
        return self::TRANSITIONS[$stage] ?? [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function transitionMap(): array
    {
        return self::TRANSITIONS;
    }

    public function transition(
        Application $application,
        string $toStage,
        ?string $note = null,
    ): Application {
        return DB::transaction(function () use ($application, $toStage, $note): Application {
            $application = Application::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $fromStage = $application->current_status;

            if (! in_array($toStage, $this->allowedTransitions($fromStage), true)) {
                throw ValidationException::withMessages([
                    'to_stage' => 'The requested pipeline stage transition is not allowed.',
                ]);
            }

            $actorId = Auth::id();

            $application->update([
                'current_status' => $toStage,
                'updated_by_id' => $actorId,
            ]);

            ApplicationStageHistory::query()->create([
                'application_id' => $application->id,
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
                'changed_by_id' => $actorId,
                'note' => $note,
                'changed_at' => now(),
            ]);
            $this->auditLogService->statusChanged(
                $application,
                'current_status',
                $fromStage,
                $toStage,
                "Application #{$application->getKey()} moved from {$fromStage} to {$toStage}.",
            );

            return $application->refresh();
        }, 3);
    }

    private function positiveInteger(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
    }
}
