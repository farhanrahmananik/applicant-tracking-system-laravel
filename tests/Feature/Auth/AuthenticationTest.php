<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_active_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'active@ats.test',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'ACTIVE@ATS.TEST',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
    }

    public function test_invalid_password_fails_authentication(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($user->refresh()->last_login_at);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_active_user_can_access_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', 'recruiter')->value('id'),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.index');
    }

    public function test_inactive_authenticated_user_is_logged_out_of_protected_routes(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
