<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentService
{
    /**
     * @param  array{search?: mixed, status?: mixed, company_id?: mixed}  $filters
     * @return LengthAwarePaginator<Department>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = in_array($filters['status'] ?? null, ['active', 'inactive'], true)
            ? $filters['status']
            : null;
        $companyId = filter_var($filters['company_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;

        return Department::query()
            ->with('company:id,name,slug,deleted_at')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
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
     * @return Collection<int, Company>
     */
    public function companyFilterOptions(): Collection
    {
        return Company::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Department
    {
        return DB::transaction(function () use ($data): Department {
            $data['slug'] = $this->generateUniqueSlug(
                (int) $data['company_id'],
                (string) ($data['slug'] ?? $data['name']),
            );

            return Department::query()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data): Department {
            $data['slug'] = $this->generateUniqueSlug(
                (int) $data['company_id'],
                (string) ($data['slug'] ?? $data['name']),
                $department,
            );

            $department->update($data);

            return $department->refresh()->load('company');
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(fn () => $department->delete());
    }

    private function generateUniqueSlug(
        int $companyId,
        string $source,
        ?Department $ignore = null,
    ): string {
        $baseSlug = Str::slug($source);
        $baseSlug = Str::limit($baseSlug !== '' ? $baseSlug : 'department', 240, '');
        $slug = $baseSlug;
        $suffix = 2;

        while (Department::withTrashed()
            ->where('company_id', $companyId)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
