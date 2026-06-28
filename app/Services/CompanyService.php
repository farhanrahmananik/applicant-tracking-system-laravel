<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyService
{
    /**
     * @param  array{search?: mixed, status?: mixed}  $filters
     * @return LengthAwarePaginator<Company>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = in_array($filters['status'] ?? null, ['active', 'inactive'], true)
            ? $filters['status']
            : null;

        return Company::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        return DB::transaction(function () use ($data): Company {
            $data['slug'] = $this->generateUniqueSlug(
                (string) ($data['slug'] ?? $data['name']),
            );

            return Company::query()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data): Company {
            $data['slug'] = $this->generateUniqueSlug(
                (string) ($data['slug'] ?? $data['name']),
                $company,
            );

            $company->update($data);

            return $company->refresh();
        });
    }

    public function delete(Company $company): void
    {
        DB::transaction(fn () => $company->delete());
    }

    private function generateUniqueSlug(string $source, ?Company $ignore = null): string
    {
        $baseSlug = Str::slug($source);
        $baseSlug = Str::limit($baseSlug !== '' ? $baseSlug : 'company', 240, '');
        $slug = $baseSlug;
        $suffix = 2;

        while (Company::withTrashed()
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
