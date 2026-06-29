<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuditLogQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AuditLog>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with('actor:id,name,email')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return array<string, Collection<int, mixed>>
     */
    public function filterOptions(): array
    {
        return [
            'actors' => User::query()
                ->select(['id', 'name', 'email'])
                ->whereHas('auditLogs')
                ->orderBy('name')
                ->get(),
            'actions' => AuditLog::query()
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'auditableTypes' => AuditLog::query()
                ->whereNotNull('auditable_type')
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    public function filteredQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return AuditLog::query()
            ->when($terms !== [], function (Builder $query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function (Builder $query) use ($term): void {
                        $query
                            ->where('summary', 'like', "%{$term}%")
                            ->orWhere('action', 'like', "%{$term}%")
                            ->orWhere('auditable_type', 'like', "%{$term}%")
                            ->orWhereHas('actor', function (Builder $query) use ($term): void {
                                $query
                                    ->where('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            })
                            ->when(
                                ctype_digit($term),
                                fn (Builder $query) => $query->orWhere('auditable_id', (int) $term),
                            );
                    });
                }
            })
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, mixed $date) => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, mixed $date) => $query->whereDate('created_at', '<=', $date),
            )
            ->when(
                $filters['actor_id'] ?? null,
                fn (Builder $query, mixed $actorId) => $query->where('actor_id', $actorId),
            )
            ->when(
                $filters['action'] ?? null,
                fn (Builder $query, mixed $action) => $query->where('action', $action),
            )
            ->when(
                $filters['auditable_type'] ?? null,
                fn (Builder $query, mixed $type) => $query->where('auditable_type', $type),
            );
    }
}
