<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_authorized_user_can_view_company_list(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        Company::factory()->create(['name' => 'Acme Industries']);

        $this->actingAs($user)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertViewIs('companies.index')
            ->assertSeeText('Acme Industries');
    }

    public function test_authorized_user_can_create_company_with_generated_unique_slug(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        Company::factory()->create([
            'name' => 'Acme Industries',
            'slug' => 'acme-industries',
        ]);

        $response = $this->actingAs($user)->post(route('companies.store'), [
            'name' => 'Acme Industries',
            'email' => 'people@acme.test',
            'phone' => '+49 30 123456',
            'website' => 'https://acme.test',
            'city' => 'Berlin',
            'country' => 'Germany',
            'is_active' => true,
            'description' => 'A recruitment technology company.',
        ]);

        $company = Company::query()->where('email', 'people@acme.test')->firstOrFail();

        $response->assertRedirect(route('companies.show', $company));
        $this->assertSame('acme-industries-2', $company->slug);
        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Industries',
            'slug' => 'acme-industries-2',
            'is_active' => true,
        ]);
    }

    public function test_company_name_is_required(): void
    {
        $user = $this->createUserWithRole('hr_manager');

        $this->actingAs($user)
            ->from(route('companies.create'))
            ->post(route('companies.store'), [
                'email' => 'missing-name@ats.test',
                'is_active' => true,
            ])
            ->assertRedirect(route('companies.create'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_authorized_user_can_update_company(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create([
            'name' => 'Original Company',
            'slug' => 'original-company',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('companies.update', $company), [
            'name' => 'Updated Company',
            'slug' => 'updated-company',
            'email' => 'updated@company.test',
            'city' => 'Hamburg',
            'country' => 'Germany',
            'is_active' => false,
        ]);

        $company->refresh();

        $response->assertRedirect(route('companies.show', $company));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company',
            'slug' => 'updated-company',
            'email' => 'updated@company.test',
            'is_active' => false,
        ]);
    }

    public function test_authorized_user_can_soft_delete_company(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'));

        $this->assertSoftDeleted($company);
    }

    public function test_unauthorized_user_cannot_access_company_routes(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $company = Company::factory()->create();

        $this->actingAs($user)->get(route('companies.index'))->assertForbidden();
        $this->actingAs($user)->get(route('companies.create'))->assertForbidden();
        $this->actingAs($user)->post(route('companies.store'), ['name' => 'Forbidden'])->assertForbidden();
        $this->actingAs($user)->get(route('companies.show', $company))->assertForbidden();
        $this->actingAs($user)->get(route('companies.edit', $company))->assertForbidden();
        $this->actingAs($user)->put(route('companies.update', $company), ['name' => 'Forbidden'])->assertForbidden();
        $this->actingAs($user)->delete(route('companies.destroy', $company))->assertForbidden();
    }

    private function createUserWithRole(string $roleSlug): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', $roleSlug)->value('id'),
        ]);

        return $user;
    }
}
