<?php

namespace App\Services\Dashboard;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * @return array{
     *     metrics: array<string, array{label: string, value: int, context: string}>,
     *     recentUsers: Collection<int, User>,
     *     roleDistribution: Collection<int, Role>
     * }
     */
    public function getData(): array
    {
        $userCounts = User::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive')
            ->firstOrFail();

        $metrics = [
            'total_users' => [
                'label' => 'Total users',
                'value' => (int) $userCounts->getAttribute('total'),
                'context' => 'Registered accounts',
            ],
            'active_users' => [
                'label' => 'Active users',
                'value' => (int) $userCounts->getAttribute('active'),
                'context' => 'Enabled for sign-in',
            ],
            'inactive_users' => [
                'label' => 'Inactive users',
                'value' => (int) $userCounts->getAttribute('inactive'),
                'context' => 'Access suspended',
            ],
            'total_roles' => [
                'label' => 'Roles',
                'value' => Role::query()->count(),
                'context' => 'Access profiles',
            ],
            'total_permissions' => [
                'label' => 'Permissions',
                'value' => Permission::query()->count(),
                'context' => 'Defined capabilities',
            ],
        ];

        $recentUsers = User::query()
            ->select(['id', 'name', 'email', 'is_active', 'last_login_at', 'created_at'])
            ->with('roles:id,name,slug')
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get();

        $roleDistribution = Role::query()
            ->select(['id', 'name', 'slug'])
            ->withCount('users')
            ->orderByDesc('users_count')
            ->orderBy('name')
            ->get();

        return compact('metrics', 'recentUsers', 'roleDistribution');
    }
}
