<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CandidateService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Candidate>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $this->allowedStatus($filters['status'] ?? null);
        $source = $this->nonEmptyString($filters['source'] ?? null);
        $availability = $this->nonEmptyString($filters['availability'] ?? null);
        $experienceMin = $this->nonNegativeNumber($filters['experience_min'] ?? null);
        $experienceMax = $this->nonNegativeNumber($filters['experience_max'] ?? null);
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Candidate::query()
            ->when($terms !== [], function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function ($query) use ($term): void {
                        $query
                            ->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%")
                            ->orWhere('location', 'like', "%{$term}%")
                            ->orWhere('skills', 'like', "%{$term}%")
                            ->orWhere('current_position', 'like', "%{$term}%");
                    });
                }
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($source !== null, fn ($query) => $query->where('source', $source))
            ->when($availability !== null, fn ($query) => $query->where('availability', $availability))
            ->when($experienceMin !== null, fn ($query) => $query->where('experience_years', '>=', $experienceMin))
            ->when($experienceMax !== null, fn ($query) => $query->where('experience_years', '<=', $experienceMax))
            ->latest('created_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @return Collection<int, string>
     */
    public function sourceOptions(): Collection
    {
        return $this->distinctOptions('source');
    }

    /**
     * @return Collection<int, string>
     */
    public function availabilityOptions(): Collection
    {
        return $this->distinctOptions('availability');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Candidate
    {
        return DB::transaction(function () use ($data): Candidate {
            $data['email'] = Str::lower(trim((string) $data['email']));

            return Candidate::query()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Candidate $candidate, array $data): Candidate
    {
        return DB::transaction(function () use ($candidate, $data): Candidate {
            $data['email'] = Str::lower(trim((string) $data['email']));
            $candidate->update($data);

            return $candidate->refresh();
        });
    }

    public function delete(Candidate $candidate): void
    {
        DB::transaction(fn () => $candidate->delete());
    }

    /**
     * @return Collection<int, string>
     */
    private function distinctOptions(string $column): Collection
    {
        return Candidate::query()
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values();
    }

    private function allowedStatus(mixed $value): ?string
    {
        return is_string($value) && in_array($value, Candidate::STATUSES, true) ? $value : null;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function nonNegativeNumber(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= 0 ? (float) $value : null;
    }
}
