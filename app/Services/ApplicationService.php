<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(
        private readonly HiringPipelineService $hiringPipelineService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Application>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $this->allowedStatus($filters['current_status'] ?? null);
        $jobPostingId = $this->positiveInteger($filters['job_posting_id'] ?? null);
        $source = $this->nonEmptyString($filters['source'] ?? null);
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Application::query()
            ->with([
                'candidate:id,first_name,last_name,email,deleted_at',
                'jobPosting:id,company_id,title,status,deleted_at',
                'jobPosting.company:id,name,deleted_at',
                'createdBy:id,name',
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
            ->when($status !== null, fn ($query) => $query->where('current_status', $status))
            ->when($jobPostingId !== null, fn ($query) => $query->where('job_posting_id', $jobPostingId))
            ->when($source !== null, fn ($query) => $query->where('source', $source))
            ->latest('applied_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Candidate>
     */
    public function candidateOptions(): Collection
    {
        return Candidate::query()
            ->select(['id', 'first_name', 'last_name', 'email', 'status'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @return Collection<int, JobPosting>
     */
    public function jobPostingOptions(): Collection
    {
        return JobPosting::query()
            ->select(['id', 'company_id', 'title', 'status'])
            ->with('company:id,name')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function sourceOptions(): \Illuminate\Support\Collection
    {
        return Application::query()
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Application
    {
        return DB::transaction(function () use ($data): Application {
            $this->lockCandidateAndJobPosting($data);
            $this->ensureNoDuplicateActiveApplication($data);

            $actorId = Auth::id();
            $data['created_by_id'] = $actorId;
            $data['updated_by_id'] = $actorId;

            return Application::query()->create($data)->load([
                'candidate',
                'jobPosting.company',
                'createdBy',
                'updatedBy',
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Application $application, array $data): Application
    {
        return DB::transaction(function () use ($application, $data): Application {
            $this->lockCandidateAndJobPosting($data);
            $this->ensureNoDuplicateActiveApplication($data, $application);

            $nextStage = $data['current_status'];
            $stageChanged = $application->current_status !== $nextStage;
            unset($data['current_status']);

            $data['updated_by_id'] = Auth::id();
            $application->update($data);

            if ($stageChanged) {
                $application = $this->hiringPipelineService->transition($application, $nextStage);
            }

            return $application->refresh()->load([
                'candidate',
                'jobPosting.company',
                'createdBy',
                'updatedBy',
            ]);
        }, 3);
    }

    public function delete(Application $application): void
    {
        DB::transaction(fn () => $application->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function lockCandidateAndJobPosting(array $data): void
    {
        Candidate::query()->whereKey($data['candidate_id'])->lockForUpdate()->firstOrFail();
        JobPosting::query()->whereKey($data['job_posting_id'])->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function ensureNoDuplicateActiveApplication(
        array $data,
        ?Application $ignore = null,
    ): void {
        if (! in_array($data['current_status'], Application::ACTIVE_STATUSES, true)) {
            return;
        }

        $duplicateExists = Application::query()
            ->where('candidate_id', $data['candidate_id'])
            ->where('job_posting_id', $data['job_posting_id'])
            ->whereIn('current_status', Application::ACTIVE_STATUSES)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'candidate_id' => 'This candidate already has an active application for the selected job posting.',
            ]);
        }
    }

    private function allowedStatus(mixed $value): ?string
    {
        return is_string($value) && in_array($value, Application::STATUSES, true)
            ? $value
            : null;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function positiveInteger(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
    }
}
