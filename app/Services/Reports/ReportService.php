<?php

namespace App\Services\Reports;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Department;
use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use App\Models\JobPosting;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportService
{
    /**
     * @return array<string, int>
     */
    public function overview(): array
    {
        return [
            'applications' => Application::query()->count(),
            'candidates' => Candidate::query()->count(),
            'job_postings' => JobPosting::query()->count(),
            'interviews' => InterviewSchedule::query()->count(),
            'offers' => Offer::query()->count(),
            'selected' => Application::query()->where('current_status', 'selected')->count(),
        ];
    }

    /**
     * @return array<string, Collection<int, mixed>>
     */
    public function filterOptions(): array
    {
        $interviewerIds = InterviewSchedule::query()
            ->select('interviewer_id')
            ->distinct()
            ->pluck('interviewer_id');

        return [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
            'departments' => Department::query()
                ->select(['id', 'company_id', 'name'])
                ->with('company:id,name,deleted_at')
                ->orderBy('name')
                ->get(),
            'jobPostings' => JobPosting::query()
                ->select(['id', 'company_id', 'department_id', 'title'])
                ->with('company:id,name,deleted_at')
                ->orderBy('title')
                ->get(),
            'interviewers' => User::query()
                ->select(['id', 'name', 'email'])
                ->whereIn('id', $interviewerIds)
                ->orderBy('name')
                ->get(),
            'candidateSources' => Candidate::query()
                ->whereNotNull('source')
                ->where('source', '<>', '')
                ->distinct()
                ->orderBy('source')
                ->pluck('source'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function applicationSummary(array $filters): array
    {
        $query = Application::query();
        $this->applyApplicationFilters($query, $filters, 'application_status');

        return [
            'metrics' => [
                'total' => (clone $query)->count(),
                'active' => (clone $query)->whereIn('current_status', Application::ACTIVE_STATUSES)->count(),
                'selected' => (clone $query)->where('current_status', 'selected')->count(),
                'terminal' => (clone $query)->whereIn('current_status', Application::TERMINAL_STATUSES)->count(),
            ],
            'rows' => $this->statusRows($query, 'current_status', Application::STATUSES),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function candidateSummary(array $filters): array
    {
        $query = Candidate::query();
        $this->applyDateRange($query, 'created_at', $filters);
        $query
            ->when(
                $filters['candidate_status'] ?? null,
                fn (Builder $query, mixed $status) => $query->where('status', $status),
            )
            ->when(
                $filters['candidate_source'] ?? null,
                fn (Builder $query, mixed $source) => $query->where('source', $source),
            );

        $sourceCounts = (clone $query)
            ->select('source')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('source')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn (Candidate $candidate): array => [
                'key' => $candidate->source ?: 'unspecified',
                'label' => $candidate->source ?: 'Unspecified',
                'count' => (int) $candidate->aggregate,
            ]);

        return [
            'metrics' => [
                'total' => (clone $query)->count(),
                'active' => (clone $query)->where('status', 'active')->count(),
                'new' => (clone $query)->where('status', 'new')->count(),
                'sources' => $sourceCounts->count(),
            ],
            'statusRows' => $this->statusRows($query, 'status', Candidate::STATUSES),
            'sourceRows' => $this->withPercentages($sourceCounts),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function jobPostingPerformance(array $filters): array
    {
        $jobPostings = JobPosting::query()
            ->select(['id', 'company_id', 'department_id', 'title', 'status', 'openings'])
            ->with(['company:id,name,deleted_at', 'department:id,name,deleted_at'])
            ->when(
                $filters['company_id'] ?? null,
                fn (Builder $query, mixed $companyId) => $query->where('company_id', $companyId),
            )
            ->when(
                $filters['department_id'] ?? null,
                fn (Builder $query, mixed $departmentId) => $query->where('department_id', $departmentId),
            )
            ->when(
                $filters['job_posting_id'] ?? null,
                fn (Builder $query, mixed $jobPostingId) => $query->whereKey($jobPostingId),
            )
            ->orderBy('title')
            ->get();

        $jobPostingIds = $jobPostings->pluck('id');
        $applicationQuery = Application::query()->whereIn('job_posting_id', $jobPostingIds);
        $this->applyDateRange($applicationQuery, 'applied_date', $filters);
        $applicationCounts = $applicationQuery
            ->select('job_posting_id')
            ->selectRaw('COUNT(*) as applications_count')
            ->selectRaw('COUNT(DISTINCT candidate_id) as candidates_count')
            ->groupBy('job_posting_id')
            ->get()
            ->keyBy('job_posting_id');

        $interviewQuery = InterviewSchedule::query()
            ->join('applications', 'applications.id', '=', 'interview_schedules.application_id')
            ->whereNull('applications.deleted_at')
            ->whereIn('applications.job_posting_id', $jobPostingIds);
        $this->applyDateRange($interviewQuery, 'interview_schedules.scheduled_at', $filters);
        $interviewCounts = $interviewQuery
            ->selectRaw('applications.job_posting_id as job_posting_id, COUNT(*) as aggregate')
            ->groupBy('applications.job_posting_id')
            ->pluck('aggregate', 'job_posting_id');

        $offerQuery = Offer::query()
            ->join('applications', 'applications.id', '=', 'offers.application_id')
            ->whereNull('applications.deleted_at')
            ->whereIn('applications.job_posting_id', $jobPostingIds);
        $this->applyDateRange($offerQuery, 'offers.created_at', $filters);
        $offerCounts = $offerQuery
            ->selectRaw('applications.job_posting_id as job_posting_id, COUNT(*) as aggregate')
            ->groupBy('applications.job_posting_id')
            ->pluck('aggregate', 'job_posting_id');

        $rows = $jobPostings->map(function (JobPosting $jobPosting) use (
            $applicationCounts,
            $interviewCounts,
            $offerCounts,
        ): array {
            $applicationCount = $applicationCounts->get($jobPosting->id);

            return [
                'jobPosting' => $jobPosting,
                'applications' => (int) ($applicationCount?->applications_count ?? 0),
                'candidates' => (int) ($applicationCount?->candidates_count ?? 0),
                'interviews' => (int) ($interviewCounts->get($jobPosting->id) ?? 0),
                'offers' => (int) ($offerCounts->get($jobPosting->id) ?? 0),
            ];
        });

        return [
            'metrics' => [
                'job_postings' => $rows->count(),
                'applications' => $rows->sum('applications'),
                'interviews' => $rows->sum('interviews'),
                'offers' => $rows->sum('offers'),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function interviewSummary(array $filters): array
    {
        $query = InterviewSchedule::query();
        $this->applyDateRange($query, 'scheduled_at', $filters);
        $this->applyJobRelationFilters($query, $filters, 'application.jobPosting');
        $query
            ->when(
                $filters['interviewer_id'] ?? null,
                fn (Builder $query, mixed $interviewerId) => $query->where('interviewer_id', $interviewerId),
            )
            ->when(
                $filters['interview_status'] ?? null,
                fn (Builder $query, mixed $status) => $query->where('status', $status),
            );

        $feedbackQuery = InterviewFeedback::query()->whereIn(
            'interview_schedule_id',
            (clone $query)->select('interview_schedules.id'),
        );

        return [
            'metrics' => [
                'total' => (clone $query)->count(),
                'upcoming' => (clone $query)
                    ->whereIn('status', ['scheduled', 'rescheduled'])
                    ->where('scheduled_at', '>=', now())
                    ->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            ],
            'statusRows' => $this->statusRows($query, 'status', InterviewSchedule::STATUSES),
            'outcomeRows' => $this->statusRows(
                $feedbackQuery,
                'recommendation',
                InterviewFeedback::RECOMMENDATIONS,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function pipelineSummary(array $filters): array
    {
        $query = Application::query();
        $this->applyApplicationFilters($query, $filters, 'pipeline_stage');

        return [
            'metrics' => [
                'total' => (clone $query)->count(),
                'screening' => (clone $query)->where('current_status', 'screening')->count(),
                'interview' => (clone $query)->where('current_status', 'interview')->count(),
                'selected' => (clone $query)->where('current_status', 'selected')->count(),
            ],
            'rows' => $this->statusRows($query, 'current_status', Application::PIPELINE_STAGES),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function offerSummary(array $filters): array
    {
        $query = Offer::query();
        $this->applyDateRange($query, 'created_at', $filters);
        $this->applyJobRelationFilters($query, $filters, 'application.jobPosting');
        $query->when(
            $filters['offer_status'] ?? null,
            fn (Builder $query, mixed $status) => $query->where('status', $status),
        );

        return [
            'metrics' => [
                'total' => (clone $query)->count(),
                'pending' => (clone $query)->whereIn('status', Offer::ACTIVE_STATUSES)->count(),
                'accepted' => (clone $query)->where('status', 'accepted')->count(),
                'declined' => (clone $query)->where('status', 'declined')->count(),
                'expired' => (clone $query)->where('status', 'expired')->count(),
            ],
            'rows' => $this->statusRows($query, 'status', Offer::STATUSES),
        ];
    }

    /**
     * @param  Builder<Application>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyApplicationFilters(Builder $query, array $filters, string $statusFilter): void
    {
        $this->applyDateRange($query, 'applied_date', $filters);
        $this->applyJobRelationFilters($query, $filters, 'jobPosting');
        $query->when(
            $filters[$statusFilter] ?? null,
            fn (Builder $query, mixed $status) => $query->where('current_status', $status),
        );
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyJobRelationFilters(Builder $query, array $filters, string $relation): void
    {
        $query
            ->when(
                $filters['company_id'] ?? null,
                fn (Builder $query, mixed $companyId) => $query->whereHas(
                    $relation,
                    fn (Builder $query) => $query->where('company_id', $companyId),
                ),
            )
            ->when(
                $filters['department_id'] ?? null,
                fn (Builder $query, mixed $departmentId) => $query->whereHas(
                    $relation,
                    fn (Builder $query) => $query->where('department_id', $departmentId),
                ),
            )
            ->when(
                $filters['job_posting_id'] ?? null,
                fn (Builder $query, mixed $jobPostingId) => $query->whereHas(
                    $relation,
                    fn (Builder $query) => $query->whereKey($jobPostingId),
                ),
            );
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyDateRange(Builder $query, string $column, array $filters): void
    {
        $query
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, mixed $date) => $query->whereDate($column, '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, mixed $date) => $query->whereDate($column, '<=', $date),
            );
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<int, string>  $statuses
     * @return Collection<int, array{key: string, label: string, count: int, percentage: float}>
     */
    private function statusRows(Builder $query, string $column, array $statuses): Collection
    {
        $counts = (clone $query)
            ->select($column)
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column);

        return $this->withPercentages(collect($statuses)->map(
            fn (string $status): array => [
                'key' => $status,
                'label' => Str::headline($status),
                'count' => (int) ($counts->get($status) ?? 0),
            ],
        ));
    }

    /**
     * @param  Collection<int, array{key: string, label: string, count: int}>  $rows
     * @return Collection<int, array{key: string, label: string, count: int, percentage: float}>
     */
    private function withPercentages(Collection $rows): Collection
    {
        $total = $rows->sum('count');

        return $rows->map(fn (array $row): array => [
            ...$row,
            'percentage' => $total > 0 ? round(($row['count'] / $total) * 100, 1) : 0.0,
        ]);
    }
}
