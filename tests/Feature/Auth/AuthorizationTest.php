<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_and_demo_user_seeders_are_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 5);
        $this->assertDatabaseCount('permissions', 14);
        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('role_user', 5);
        $this->assertDatabaseCount('permission_role', 28);
        $this->assertTrue(User::query()->where('email', 'superadmin@ats.test')->firstOrFail()->isSuperAdmin());
    }

    public function test_user_role_helpers_work(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', 'recruiter')->value('id'),
            Role::query()->where('slug', 'interviewer')->value('id'),
        ]);

        $this->assertTrue($user->hasRole('recruiter'));
        $this->assertTrue($user->hasAnyRole(['hr_manager', 'interviewer']));
        $this->assertFalse($user->hasRole('candidate'));
        $this->assertFalse($user->hasAnyRole([]));
    }

    public function test_user_permission_helper_uses_role_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', 'recruiter')->value('id'),
        ]);

        $this->assertTrue($user->hasPermissionTo('access-dashboard'));
        $this->assertFalse($user->hasPermissionTo('manage-users'));
    }

    public function test_permission_middleware_blocks_unauthorized_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Route::middleware(['web', 'auth', 'active', 'permission:manage-users'])
            ->get('/test/manage-users', fn () => 'allowed');

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', 'recruiter')->value('id'),
        ]);

        $this->actingAs($user)
            ->get('/test/manage-users')
            ->assertForbidden();
    }

    public function test_role_middleware_supports_comma_separated_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Route::middleware(['web', 'auth', 'active', 'role:hr_manager,recruiter'])
            ->get('/test/recruitment-team', fn () => 'allowed');

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', 'recruiter')->value('id'),
        ]);

        $this->actingAs($user)
            ->get('/test/recruitment-team')
            ->assertOk()
            ->assertSee('allowed');
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', 'super_admin')->value('id'),
        ]);

        Gate::define('explicitly-denied-action', fn (): bool => false);

        $this->assertTrue(Gate::forUser($user)->allows('explicitly-denied-action'));
    }
}
