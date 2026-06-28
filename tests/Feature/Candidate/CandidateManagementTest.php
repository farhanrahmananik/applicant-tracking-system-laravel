<?php

namespace Tests\Feature\Candidate;

use App\Models\Candidate;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_candidates_index(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create([
            'first_name' => 'Avery',
            'last_name' => 'Stone',
        ]);

        $this->actingAs($user)
            ->get(route('candidates.index'))
            ->assertOk()
            ->assertViewIs('candidates.index')
            ->assertSeeText($candidate->full_name);
    }

    public function test_authorized_user_can_create_candidate_without_creating_user_account(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $userCount = User::query()->count();

        $response = $this->actingAs($user)->post(
            route('candidates.store'),
            $this->validPayload([
                'first_name' => 'Jordan',
                'last_name' => 'Lee',
                'email' => 'JORDAN.LEE@EXAMPLE.TEST',
            ]),
        );

        $candidate = Candidate::query()->where('email', 'jordan.lee@example.test')->firstOrFail();

        $response->assertRedirect(route('candidates.show', $candidate));
        $this->assertNull($candidate->user_id);
        $this->assertSame($userCount, User::query()->count());
    }

    public function test_validation_errors_appear_for_required_and_invalid_data(): void
    {
        $user = $this->createUserWithRole('recruiter');

        $this->actingAs($user)
            ->from(route('candidates.create'))
            ->post(route('candidates.store'), [
                'email' => 'not-an-email',
                'experience_years' => 81,
                'expected_salary' => -1,
                'status' => 'shortlisted',
            ])
            ->assertRedirect(route('candidates.create'))
            ->assertSessionHasErrors([
                'first_name',
                'email',
                'experience_years',
                'expected_salary',
                'status',
            ]);

        $this->assertDatabaseCount('candidates', 0);
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        $user = $this->createUserWithRole('recruiter');
        Candidate::factory()->create(['email' => 'duplicate@example.test']);

        $this->actingAs($user)
            ->from(route('candidates.create'))
            ->post(route('candidates.store'), $this->validPayload([
                'email' => 'DUPLICATE@EXAMPLE.TEST',
            ]))
            ->assertRedirect(route('candidates.create'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Candidate::query()->where('email', 'duplicate@example.test')->count());
    }

    public function test_authorized_user_can_view_candidate_show_page(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create([
            'first_name' => 'Morgan',
            'last_name' => 'Reed',
            'skills' => 'Laravel, MySQL, REST APIs',
        ]);

        $this->actingAs($user)
            ->get(route('candidates.show', $candidate))
            ->assertOk()
            ->assertViewIs('candidates.show')
            ->assertSeeText('Morgan Reed')
            ->assertSeeText('Laravel, MySQL, REST APIs');
    }

    public function test_authorized_user_can_update_candidate(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create([
            'email' => 'before@example.test',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->put(
            route('candidates.update', $candidate),
            $this->validPayload([
                'first_name' => 'Updated',
                'last_name' => 'Candidate',
                'email' => 'updated@example.test',
                'status' => 'active',
            ]),
        );

        $candidate->refresh();

        $response->assertRedirect(route('candidates.show', $candidate));
        $this->assertSame('Updated Candidate', $candidate->full_name);
        $this->assertSame('updated@example.test', $candidate->email);
        $this->assertSame('active', $candidate->status);
    }

    public function test_authorized_user_can_soft_delete_candidate(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $candidate = Candidate::factory()->create();

        $this->actingAs($user)
            ->delete(route('candidates.destroy', $candidate))
            ->assertRedirect(route('candidates.index'));

        $this->assertSoftDeleted($candidate);
    }

    public function test_search_works_by_name_email_skills_and_current_position(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create([
            'first_name' => 'Avery',
            'last_name' => 'Stone',
            'email' => 'avery.stone@example.test',
            'skills' => 'Kubernetes, GraphQL, PHP',
            'current_position' => 'Principal Platform Engineer',
        ]);
        $otherCandidate = Candidate::factory()->create([
            'first_name' => 'Unrelated',
            'last_name' => 'Person',
            'skills' => 'Accounting',
            'current_position' => 'Finance Analyst',
        ]);

        foreach (['Avery Stone', 'avery.stone', 'Kubernetes', 'Principal Engineer'] as $search) {
            $this->actingAs($user)
                ->get(route('candidates.index', ['search' => $search]))
                ->assertOk()
                ->assertSeeText($candidate->full_name)
                ->assertDontSeeText($otherCandidate->full_name);
        }
    }

    public function test_filters_work_by_status_source_and_availability(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create([
            'first_name' => 'Matching',
            'last_name' => 'Candidate',
            'status' => 'active',
            'source' => 'referral',
            'availability' => 'immediate',
        ]);
        $otherCandidate = Candidate::factory()->create([
            'first_name' => 'Other',
            'last_name' => 'Candidate',
            'status' => 'inactive',
            'source' => 'job_board',
            'availability' => 'two_months',
        ]);

        foreach ([
            ['status' => 'active'],
            ['source' => 'referral'],
            ['availability' => 'immediate'],
        ] as $filter) {
            $this->actingAs($user)
                ->get(route('candidates.index', $filter))
                ->assertOk()
                ->assertSeeText($candidate->full_name)
                ->assertDontSeeText($otherCandidate->full_name);
        }
    }

    public function test_unauthorized_user_cannot_access_candidate_management_routes(): void
    {
        $user = $this->createUserWithRole('candidate');
        $candidate = Candidate::factory()->create();

        $this->actingAs($user)->get(route('candidates.index'))->assertForbidden();
        $this->actingAs($user)->get(route('candidates.create'))->assertForbidden();
        $this->actingAs($user)->post(route('candidates.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('candidates.show', $candidate))->assertForbidden();
        $this->actingAs($user)->get(route('candidates.edit', $candidate))->assertForbidden();
        $this->actingAs($user)->put(route('candidates.update', $candidate), [])->assertForbidden();
        $this->actingAs($user)->delete(route('candidates.destroy', $candidate))->assertForbidden();
    }

    public function test_sidebar_visibility_and_recruiter_delete_access_follow_permissions(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();

        $this->actingAs($recruiter)
            ->get(route('candidates.index'))
            ->assertOk()
            ->assertSee(route('candidates.index'))
            ->assertSeeText('Candidates');

        $this->actingAs($recruiter)
            ->delete(route('candidates.destroy', $candidate))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'first_name' => 'Taylor',
            'last_name' => 'Morgan',
            'email' => 'taylor.morgan@example.test',
            'phone' => '+49 30 123456',
            'location' => 'Berlin, Germany',
            'source' => 'referral',
            'experience_years' => 6.5,
            'skills' => 'PHP, Laravel, MySQL',
            'current_position' => 'Backend Engineer',
            'expected_salary' => 75000,
            'availability' => 'one_month',
            'status' => 'new',
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
