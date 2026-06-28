<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(
            fn (User $user): ?bool => $user->isSuperAdmin() ? true : null,
        );

        $permissions = config('ats_permissions.permissions', []);

        if (! is_array($permissions)) {
            return;
        }

        foreach (array_keys($permissions) as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            Gate::define(
                $permission,
                fn (User $user): bool => $user->permissions()
                    ->where('permissions.slug', $permission)
                    ->exists(),
            );
        }
    }
}
