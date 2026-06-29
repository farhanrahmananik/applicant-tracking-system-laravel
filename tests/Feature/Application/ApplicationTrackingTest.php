<?php

namespace Tests\Feature\Application;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_application_index(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertViewIs('applications.index')
            ->assertSeeText($application->candidate->full_name)
            ->assertSeeText($application->jobPosting->title);
    }

    public function test_authorized_user_can_create_application(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        $jobPosting = JobPosting::factory()->open()->create();

        $response = $this->actingAs($user)->post(
            route('applications.store'),
            $this->validPayload($candidate, $jobPosting, [
                'source' => ' referral ',
                'notes' => ' Strong backend profile. ',
            ]),
        );

        $application = Application::query()->firstOrFail();

        $response->assertRedirect(route('applications.show', $application));
        $this->assertSame($user->id, $application->created_by_id);
        $this->assertSame($user->id, $application->updated_by_id);
        $this->assertSame('referral', $application->source);
        $this->assertSame('Strong backend profile.', $application->notes);
    }

    public function test_duplicate_active_application_for_same_candidate_and_job_is_blocked(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        $jobPosting = JobPosting::factory()->open()->create();

        Application::factory()->for($candidate)->for($jobPosting)->create([
            'current_status' => 'screening',
        ]);

        $this->actingAs($user)
            ->from(route('applications.create'))
            ->post(
                route('applications.store'),
                $this->validPayload($candidate, $jobPosting, ['current_status' => 'shortlisted']),
            )
            ->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors([
                'candidate_id' => 'This candidate already has an active application for the selected job posting.',
            ]);

        $this->assertDatabaseCount('applications', 1);
    }

    public function test_terminal_application_allows_a_new_active_application(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        $jobPosting = JobPosting::factory()->open()->create();

        Application::factory()->for($candidate)->for($jobPosting)->create([
            'current_status' => 'rejected',
        ]);

        $this->actingAs($user)
            ->post(route('applications.store'), $this->validPayload($candidate, $jobPosting))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('applications', 2);
        $this->assertSame(
            1,
            Application::query()
                ->where('candidate_id', $candidate->id)
                ->where('job_posting_id', $jobPosting->id)
                ->whereIn('current_status', Application::ACTIVE_STATUSES)
                ->count(),
        );
    }

    public function test_authorized_user_can_update_application(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create([
            'current_status' => 'applied',
            'notes' => null,
        ]);

        $response = $this->actingAs($user)->put(
            route('applications.update', $application),
            $this->validPayload($application->candidate, $application->jobPosting, [
                'current_status' => 'shortlisted',
                'notes' => 'Ready for the next review step.',
            ]),
        );

        $application->refresh();

        $response->assertRedirect(route('applications.show', $application));
        $this->assertSame('shortlisted', $application->current_status);
        $this->assertSame('Ready for the next review step.', $application->notes);
        $this->assertSame($user->id, $application->updated_by_id);
    }

    public function test_update_cannot_create_a_second_active_application(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        $jobPosting = JobPosting::factory()->open()->create();
        Application::factory()->for($candidate)->for($jobPosting)->create([
            'current_status' => 'applied',
        ]);
        $terminalApplication = Application::factory()->for($candidate)->for($jobPosting)->create([
            'current_status' => 'withdrawn',
        ]);

        $this->actingAs($user)
            ->from(route('applications.edit', $terminalApplication))
            ->put(
                route('applications.update', $terminalApplication),
                $this->validPayload($candidate, $jobPosting, ['current_status' => 'screening']),
            )
            ->assertRedirect(route('applications.edit', $terminalApplication))
            ->assertSessionHasErrors('candidate_id');

        $this->assertSame('withdrawn', $terminalApplication->refresh()->current_status);
    }

    public function test_application_show_displays_status_and_details(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create([
            'current_status' => 'shortlisted',
            'source' => 'career_site',
            'notes' => 'Strong match for the role.',
        ]);

        $this->actingAs($user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertViewIs('applications.show')
            ->assertSeeText($application->candidate->full_name)
            ->assertSeeText($application->jobPosting->title)
            ->assertSeeText('Shortlisted')
            ->assertSee('application-status-shortlisted', false)
            ->assertSeeText('Strong match for the role.');
    }

    public function test_candidate_and_job_pages_display_related_application(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create([
            'current_status' => 'screening',
        ]);

        $this->actingAs($user)
            ->get(route('candidates.show', $application->candidate))
            ->assertOk()
            ->assertSeeText('Applications')
            ->assertSeeText($application->jobPosting->title)
            ->assertSeeText('Screening');

        $this->actingAs($user)
            ->get(route('job-postings.show', $application->jobPosting))
            ->assertOk()
            ->assertSeeText('Applications')
            ->assertSeeText($application->candidate->full_name)
            ->assertSeeText('Screening');
    }

    public function test_search_and_filters_narrow_application_index(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $matching = Application::factory()->create([
            'current_status' => 'screening',
            'source' => 'referral',
        ]);
        $matching->candidate->update([
            'first_name' => 'Distinctive',
            'last_name' => 'Applicant',
            'email' => 'distinctive@example.test',
        ]);
        $other = Application::factory()->create([
            'current_status' => 'rejected',
            'source' => 'job_board',
        ]);

        foreach ([
            ['search' => 'Distinctive Applicant'],
            ['search' => $matching->jobPosting->title],
            ['current_status' => 'screening'],
            ['job_posting_id' => $matching->job_posting_id],
            ['source' => 'referral'],
        ] as $filter) {
            $this->actingAs($user)
                ->get(route('applications.index', $filter))
                ->assertOk()
                ->assertSeeText('Distinctive Applicant')
                ->assertDontSeeText($other->candidate->full_name);
        }
    }

    public function test_validation_rejects_missing_records_future_date_and_invalid_status(): void
    {
        $user = $this->createUserWithRole('recruiter');

        $this->actingAs($user)
            ->from(route('applications.create'))
            ->post(route('applications.store'), [
                'candidate_id' => 999999,
                'job_posting_id' => 999999,
                'source' => str_repeat('a', 101),
                'applied_date' => now()->addDay()->toDateString(),
                'current_status' => 'offer',
                'notes' => str_repeat('a', 5001),
            ])
            ->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors([
                'candidate_id',
                'job_posting_id',
                'source',
                'applied_date',
                'current_status',
                'notes',
            ]);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_unauthorized_user_cannot_access_application_routes(): void
    {
        $user = $this->createUserWithRole('candidate');
        $application = Application::factory()->create();

        $this->actingAs($user)->get(route('applications.index'))->assertForbidden();
        $this->actingAs($user)->get(route('applications.create'))->assertForbidden();
        $this->actingAs($user)->post(route('applications.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('applications.show', $application))->assertForbidden();
        $this->actingAs($user)->get(route('applications.edit', $application))->assertForbidden();
        $this->actingAs($user)->put(route('applications.update', $application), [])->assertForbidden();
        $this->actingAs($user)->delete(route('applications.destroy', $application))->assertForbidden();
    }

    public function test_hr_manager_can_soft_delete_application_and_recruiter_cannot(): void
    {
        $hrManager = $this->createUserWithRole('hr_manager');
        $recruiter = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create();

        $this->actingAs($recruiter)
            ->delete(route('applications.destroy', $application))
            ->assertForbidden();

        $this->actingAs($hrManager)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect(route('applications.index'));

        $this->assertSoftDeleted($application);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        Candidate $candidate,
        JobPosting $jobPosting,
        array $overrides = [],
    ): array {
        return array_replace([
            'candidate_id' => $candidate->id,
            'job_posting_id' => $jobPosting->id,
            'source' => 'career_site',
            'applied_date' => now()->toDateString(),
            'current_status' => 'applied',
            'notes' => 'Initial application review.',
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
