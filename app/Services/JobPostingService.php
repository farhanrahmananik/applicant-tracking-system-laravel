<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobPosting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobPostingService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<JobPosting>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $companyId = $this->positiveInteger($filters['company_id'] ?? null);
        $departmentId = $this->positiveInteger($filters['department_id'] ?? null);
        $status = $this->allowedFilter($filters['status'] ?? null, JobPosting::STATUSES);
        $employmentType = $this->allowedFilter(
            $filters['employment_type'] ?? null,
            JobPosting::EMPLOYMENT_TYPES,
        );
        $workplaceType = $this->allowedFilter(
            $filters['workplace_type'] ?? null,
            JobPosting::WORKPLACE_TYPES,
        );

        return JobPosting::query()
            ->with([
                'company:id,name,slug,deleted_at',
                'department:id,company_id,name,deleted_at',
                'createdBy:id,name',
            ])
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($employmentType !== null, fn ($query) => $query->where('employment_type', $employmentType))
            ->when($workplaceType !== null, fn ($query) => $query->where('workplace_type', $workplaceType))
            ->latest('created_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Company>
     */
    public function companyOptions(?int $includeCompanyId = null): Collection
    {
        return Company::query()
            ->select(['id', 'name', 'is_active'])
            ->where(function ($query) use ($includeCompanyId): void {
                $query
                    ->where('is_active', true)
                    ->when(
                        $includeCompanyId !== null,
                        fn ($query) => $query->orWhereKey($includeCompanyId),
                    );
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Department>
     */
    public function departmentOptions(?int $includeDepartmentId = null): Collection
    {
        return Department::query()
            ->select(['id', 'company_id', 'name', 'is_active'])
            ->with('company:id,name')
            ->whereHas('company', fn ($query) => $query->whereNull('companies.deleted_at'))
            ->where(function ($query) use ($includeDepartmentId): void {
                $query
                    ->where('is_active', true)
                    ->when(
                        $includeDepartmentId !== null,
                        fn ($query) => $query->orWhereKey($includeDepartmentId),
                    );
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): JobPosting
    {
        return DB::transaction(function () use ($data): JobPosting {
            $this->ensureDepartmentBelongsToCompany($data);
            $data = $this->prepareData($data);

            $jobPosting = JobPosting::query()->create($data)->load([
                'company',
                'department',
                'createdBy',
                'updatedBy',
            ]);
            $this->auditLogService->created(
                $jobPosting,
                "Job posting {$jobPosting->title} created.",
            );

            return $jobPosting;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(JobPosting $jobPosting, array $data): JobPosting
    {
        return DB::transaction(function () use ($jobPosting, $data): JobPosting {
            $before = $this->auditLogService->snapshot($jobPosting);
            $this->ensureDepartmentBelongsToCompany($data);
            $data = $this->prepareData($data, $jobPosting);

            $jobPosting->update($data);
            $this->auditLogService->updated(
                $jobPosting,
                $before,
                "Job posting {$jobPosting->title} updated.",
            );

            return $jobPosting->refresh()->load([
                'company',
                'department',
                'createdBy',
                'updatedBy',
            ]);
        });
    }

    public function delete(JobPosting $jobPosting): void
    {
        DB::transaction(function () use ($jobPosting): void {
            $before = $this->auditLogService->snapshot($jobPosting);
            $jobPosting->delete();
            $this->auditLogService->deleted(
                $jobPosting,
                "Job posting {$jobPosting->title} deleted.",
                $before,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareData(array $data, ?JobPosting $jobPosting = null): array
    {
        $data['slug'] = $this->generateUniqueSlug(
            (int) $data['company_id'],
            (string) ($data['slug'] ?? $data['title']),
            $jobPosting,
        );

        $actorId = Auth::id();

        if ($actorId !== null) {
            $data['updated_by_id'] = $actorId;

            if ($jobPosting === null) {
                $data['created_by_id'] = $actorId;
            }
        }

        if ($data['status'] === 'open' && empty($data['published_at'])) {
            $data['published_at'] = $jobPosting?->published_at ?? now();
        }

        if (
            $jobPosting?->published_at !== null
            && $data['status'] !== 'open'
            && empty($data['published_at'])
        ) {
            unset($data['published_at']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function ensureDepartmentBelongsToCompany(array $data): void
    {
        if (empty($data['department_id'])) {
            return;
        }

        $exists = Department::query()
            ->whereKey($data['department_id'])
            ->where('company_id', $data['company_id'])
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'department_id' => 'The selected department does not belong to the selected company.',
            ]);
        }
    }

    private function generateUniqueSlug(
        int $companyId,
        string $source,
        ?JobPosting $ignore = null,
    ): string {
        $baseSlug = Str::slug($source);
        $baseSlug = Str::limit($baseSlug !== '' ? $baseSlug : 'job-posting', 240, '');
        $slug = $baseSlug;
        $suffix = 2;

        while (JobPosting::withTrashed()
            ->where('company_id', $companyId)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function allowedFilter(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    private function positiveInteger(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
    }
}
