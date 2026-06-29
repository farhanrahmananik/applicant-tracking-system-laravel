<?php

namespace Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_active_user_with_permission_can_access_dashboard(): void
    {
        $user = $this->createUserWithRole('recruiter');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.index');
    }

    public function test_dashboard_displays_expected_metrics_and_sections(): void
    {
        $user = $this->createUserWithRole('hr_manager', [
            'name' => 'Dashboard Manager',
            'email' => 'dashboard.manager@ats.test',
        ]);

        $recruiter = User::factory()->create([
            'name' => 'Recent Recruiter',
            'email' => 'recent.recruiter@ats.test',
        ]);
        $recruiter->roles()->sync([
            Role::query()->where('slug', 'recruiter')->value('id'),
        ]);

        $inactiveUser = User::factory()->inactive()->create([
            'name' => 'Inactive Interviewer',
            'email' => 'inactive.interviewer@ats.test',
        ]);
        $inactiveUser->roles()->sync([
            Role::query()->where('slug', 'interviewer')->value('id'),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('metrics', function (array $metrics): bool {
                return $metrics['total_users']['value'] === 3
                    && $metrics['active_users']['value'] === 2
                    && $metrics['inactive_users']['value'] === 1
                    && $metrics['total_roles']['value'] === 5
                    && $metrics['total_permissions']['value'] === 42;
            })
            ->assertViewHas('recentUsers', fn ($users): bool => $users->count() === 3)
            ->assertViewHas('roleDistribution', fn ($roles): bool => $roles->count() === 5)
            ->assertSeeText('Account metrics')
            ->assertSeeText('Recent users')
            ->assertSeeText('Role distribution')
            ->assertSeeText('Upcoming ATS Modules')
            ->assertSeeText('Recent Recruiter')
            ->assertSeeText('Company')
            ->assertSeeText('Interview')
            ->assertSeeText('Reports');
    }

    public function test_authenticated_user_without_dashboard_permission_is_forbidden(): void
    {
        $user = $this->createUserWithRole('candidate');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUserWithRole(string $roleSlug, array $attributes = []): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create($attributes);
        $user->roles()->sync([
            Role::query()->where('slug', $roleSlug)->value('id'),
        ]);

        return $user;
    }
}
