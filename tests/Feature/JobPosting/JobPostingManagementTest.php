<?php

namespace Tests\Feature\JobPosting;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobPosting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobPostingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_job_postings_index(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $jobPosting = JobPosting::factory()->create(['title' => 'Senior Backend Engineer']);

        $this->actingAs($user)
            ->get(route('job-postings.index'))
            ->assertOk()
            ->assertViewIs('job-postings.index')
            ->assertSeeText($jobPosting->title);
    }

    public function test_unauthorized_user_cannot_view_job_postings_index(): void
    {
        $user = $this->createUserWithRole('candidate');

        $this->actingAs($user)
            ->get(route('job-postings.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_job_posting(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $department = Department::factory()->create();

        $response = $this->actingAs($user)->post(
            route('job-postings.store'),
            $this->validPayload($department->company, $department, [
                'title' => 'Platform Engineer',
            ]),
        );

        $jobPosting = JobPosting::query()->where('title', 'Platform Engineer')->firstOrFail();

        $response->assertRedirect(route('job-postings.show', $jobPosting));
        $this->assertDatabaseHas('job_postings', [
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'title' => 'Platform Engineer',
            'slug' => 'platform-engineer',
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);
    }

    public function test_validation_fails_for_invalid_required_fields(): void
    {
        $user = $this->createUserWithRole('recruiter');

        $this->actingAs($user)
            ->from(route('job-postings.create'))
            ->post(route('job-postings.store'), [
                'employment_type' => 'permanent',
                'workplace_type' => 'virtual',
                'openings' => 0,
                'salary_min' => 100000,
                'salary_max' => 50000,
                'status' => 'published',
            ])
            ->assertRedirect(route('job-postings.create'))
            ->assertSessionHasErrors([
                'company_id',
                'title',
                'employment_type',
                'workplace_type',
                'openings',
                'salary_max',
                'description',
                'status',
            ]);

        $this->assertDatabaseCount('job_postings', 0);
    }

    public function test_department_must_belong_to_selected_company(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $selectedCompany = Company::factory()->create();
        $otherDepartment = Department::factory()->create();

        $this->actingAs($user)
            ->from(route('job-postings.create'))
            ->post(
                route('job-postings.store'),
                $this->validPayload($selectedCompany, $otherDepartment),
            )
            ->assertRedirect(route('job-postings.create'))
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseCount('job_postings', 0);
    }

    public function test_authorized_user_can_update_job_posting(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $department = Department::factory()->create();
        $jobPosting = JobPosting::factory()->forDepartment($department)->create([
            'title' => 'Original Position',
            'slug' => 'original-position',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->put(
            route('job-postings.update', $jobPosting),
            $this->validPayload($department->company, $department, [
                'title' => 'Updated Position',
                'slug' => 'updated-position',
                'status' => 'paused',
            ]),
        );

        $jobPosting->refresh();

        $response->assertRedirect(route('job-postings.show', $jobPosting));
        $this->assertDatabaseHas('job_postings', [
            'id' => $jobPosting->id,
            'title' => 'Updated Position',
            'slug' => 'updated-position',
            'status' => 'paused',
            'updated_by_id' => $user->id,
        ]);
    }

    public function test_authorized_user_can_soft_delete_job_posting(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $jobPosting = JobPosting::factory()->create();

        $this->actingAs($user)
            ->delete(route('job-postings.destroy', $jobPosting))
            ->assertRedirect(route('job-postings.index'));

        $this->assertSoftDeleted($jobPosting);
    }

    public function test_open_status_sets_published_at_when_empty_and_later_status_preserves_it(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $company = Company::factory()->create();

        $this->actingAs($user)->post(
            route('job-postings.store'),
            $this->validPayload($company, null, [
                'title' => 'Open Position',
                'status' => 'open',
                'published_at' => null,
            ]),
        )->assertSessionHasNoErrors();

        $jobPosting = JobPosting::query()->where('title', 'Open Position')->firstOrFail();
        $publishedAt = $jobPosting->published_at;

        $this->assertNotNull($publishedAt);

        $this->actingAs($user)->put(
            route('job-postings.update', $jobPosting),
            $this->validPayload($company, null, [
                'title' => 'Open Position',
                'status' => 'paused',
                'published_at' => null,
            ]),
        )->assertSessionHasNoErrors();

        $this->assertTrue($jobPosting->refresh()->published_at->equalTo($publishedAt));
    }

    public function test_slug_is_unique_per_company_and_reusable_by_another_company(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        JobPosting::factory()->for($firstCompany, 'company')->create([
            'title' => 'Software Engineer',
            'slug' => 'software-engineer',
        ]);

        $this->actingAs($user)->post(
            route('job-postings.store'),
            $this->validPayload($firstCompany, null, ['title' => 'Software Engineer']),
        )->assertSessionHasNoErrors();

        $this->actingAs($user)->post(
            route('job-postings.store'),
            $this->validPayload($secondCompany, null, ['title' => 'Software Engineer']),
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('job_postings', [
            'company_id' => $firstCompany->id,
            'slug' => 'software-engineer-2',
        ]);
        $this->assertDatabaseHas('job_postings', [
            'company_id' => $secondCompany->id,
            'slug' => 'software-engineer',
        ]);
    }

    public function test_sidebar_visibility_follows_view_permission(): void
    {
        $viewer = $this->createUserWithRole('interviewer');

        $this->actingAs($viewer)
            ->get(route('job-postings.index'))
            ->assertOk()
            ->assertSee(route('job-postings.index'))
            ->assertSeeText('Job Postings');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        Company $company,
        ?Department $department = null,
        array $overrides = [],
    ): array {
        return array_replace([
            'company_id' => $company->id,
            'department_id' => $department?->id,
            'title' => 'Backend Engineer',
            'employment_type' => 'full_time',
            'workplace_type' => 'hybrid',
            'location' => 'Berlin, Germany',
            'openings' => 2,
            'salary_min' => 60000,
            'salary_max' => 80000,
            'currency' => 'EUR',
            'experience_level' => 'Mid level',
            'description' => 'Build and maintain ATS platform services.',
            'requirements' => 'Strong PHP and Laravel experience.',
            'responsibilities' => 'Deliver reliable product features.',
            'benefits' => 'Flexible work arrangements.',
            'status' => 'draft',
            'published_at' => null,
            'closes_at' => now()->addMonth()->toDateString(),
        ], $overrides);
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
