<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = config('ats_permissions.permissions', []);
        $roles = config('ats_permissions.roles', []);

        if (! is_array($permissions) || ! is_array($roles)) {
            throw new RuntimeException('ATS role and permission configuration must be arrays.');
        }

        DB::transaction(function () use ($permissions, $roles): void {
            $permissionModels = collect($permissions)->mapWithKeys(
                function (mixed $definition, mixed $slug): array {
                    if (! is_string($slug) || ! is_array($definition)) {
                        throw new RuntimeException('Each ATS permission must have a string slug and array definition.');
                    }

                    $permission = Permission::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $definition['name'] ?? $slug,
                            'description' => $definition['description'] ?? null,
                        ],
                    );

                    return [$slug => $permission];
                },
            );

            foreach ($roles as $slug => $definition) {
                if (! is_string($slug) || ! is_array($definition)) {
                    throw new RuntimeException('Each ATS role must have a string slug and array definition.');
                }

                $role = Role::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $definition['name'] ?? $slug,
                        'description' => $definition['description'] ?? null,
                    ],
                );

                $configuredPermissions = $definition['permissions'] ?? [];

                if (! is_array($configuredPermissions)) {
                    throw new RuntimeException("Permissions for role [{$slug}] must be an array.");
                }

                if (in_array('*', $configuredPermissions, true) && $configuredPermissions !== ['*']) {
                    throw new RuntimeException("Wildcard permissions for role [{$slug}] cannot be combined with other values.");
                }

                $unknownPermissions = array_diff(
                    $configuredPermissions,
                    ['*', ...$permissionModels->keys()->all()],
                );

                if ($unknownPermissions !== []) {
                    throw new RuntimeException(
                        "Role [{$slug}] contains unknown permissions: ".implode(', ', $unknownPermissions),
                    );
                }

                $permissionIds = $configuredPermissions === ['*']
                    ? $permissionModels->pluck('id')
                    : $permissionModels->only($configuredPermissions)->pluck('id');

                $role->permissions()->sync($permissionIds);
            }
        });
    }
}
