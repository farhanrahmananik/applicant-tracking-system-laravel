<?php

namespace Tests\Feature\Department;

use App\Models\Company;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_authorized_user_can_view_department_list(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create(['name' => 'Acme Industries']);
        Department::factory()->for($company)->create(['name' => 'Engineering']);

        $this->actingAs($user)
            ->get(route('departments.index'))
            ->assertOk()
            ->assertViewIs('departments.index')
            ->assertSeeText('Engineering')
            ->assertSeeText('Acme Industries');
    }

    public function test_authorized_user_can_create_department_under_company(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->post(route('departments.store'), [
            'company_id' => $company->id,
            'name' => 'People Operations',
            'email' => 'people@company.test',
            'phone' => '+49 30 987654',
            'location' => 'Berlin HQ',
            'description' => 'Supports employees and organizational development.',
            'is_active' => true,
        ]);

        $department = Department::query()->where('email', 'people@company.test')->firstOrFail();

        $response->assertRedirect(route('departments.show', $department));
        $this->assertDatabaseHas('departments', [
            'company_id' => $company->id,
            'name' => 'People Operations',
            'slug' => 'people-operations',
            'is_active' => true,
        ]);
    }

    public function test_department_name_is_required(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->from(route('departments.create'))
            ->post(route('departments.store'), [
                'company_id' => $company->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('departments.create'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_company_is_required_and_must_exist(): void
    {
        $user = $this->createUserWithRole('hr_manager');

        $this->actingAs($user)
            ->from(route('departments.create'))
            ->post(route('departments.store'), [
                'name' => 'Engineering',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('company_id');

        $this->actingAs($user)
            ->from(route('departments.create'))
            ->post(route('departments.store'), [
                'company_id' => 999999,
                'name' => 'Engineering',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('company_id');

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_slug_is_unique_per_company_but_reusable_by_another_company(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        Department::factory()->for($firstCompany)->create(['slug' => 'operations']);
        Department::factory()->for($secondCompany)->create(['slug' => 'operations']);

        $this->actingAs($user)
            ->from(route('departments.create'))
            ->post(route('departments.store'), [
                'company_id' => $firstCompany->id,
                'name' => 'Another Operations Team',
                'slug' => 'operations',
                'is_active' => true,
            ])
            ->assertRedirect(route('departments.create'))
            ->assertSessionHasErrors('slug');

        $this->assertSame(2, Department::query()->where('slug', 'operations')->count());
    }

    public function test_authorized_user_can_update_department(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create();
        $department = Department::factory()->for($company)->create([
            'name' => 'Original Department',
            'slug' => 'original-department',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('departments.update', $department), [
            'company_id' => $company->id,
            'name' => 'Updated Department',
            'slug' => 'updated-department',
            'email' => 'updated.department@company.test',
            'location' => 'Munich Office',
            'is_active' => false,
        ]);

        $department->refresh();

        $response->assertRedirect(route('departments.show', $department));
        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'company_id' => $company->id,
            'name' => 'Updated Department',
            'slug' => 'updated-department',
            'is_active' => false,
        ]);
    }

    public function test_authorized_user_can_soft_delete_department(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $department = Department::factory()->create();

        $this->actingAs($user)
            ->delete(route('departments.destroy', $department))
            ->assertRedirect(route('departments.index'));

        $this->assertSoftDeleted($department);
    }

    public function test_unauthorized_user_cannot_access_department_routes(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $department = Department::factory()->create();

        $this->actingAs($user)->get(route('departments.index'))->assertForbidden();
        $this->actingAs($user)->get(route('departments.create'))->assertForbidden();
        $this->actingAs($user)->post(route('departments.store'), ['name' => 'Forbidden'])->assertForbidden();
        $this->actingAs($user)->get(route('departments.show', $department))->assertForbidden();
        $this->actingAs($user)->get(route('departments.edit', $department))->assertForbidden();
        $this->actingAs($user)->put(route('departments.update', $department), ['name' => 'Forbidden'])->assertForbidden();
        $this->actingAs($user)->delete(route('departments.destroy', $department))->assertForbidden();
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
