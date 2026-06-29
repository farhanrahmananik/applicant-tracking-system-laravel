<?php

namespace Tests\Feature\Pipeline;

use App\Models\Application;
use App\Models\ApplicationStageHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HiringPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_pipeline_board(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create([
            'current_status' => 'screening',
        ]);

        $this->actingAs($user)
            ->get(route('pipeline.index'))
            ->assertOk()
            ->assertViewIs('pipeline.index')
            ->assertSeeText('Hiring Pipeline')
            ->assertSeeText($application->candidate->full_name)
            ->assertSeeText($application->jobPosting->title)
            ->assertSeeText('Applied')
            ->assertSeeText('Screening')
            ->assertSeeText('Interview')
            ->assertSeeText('Selected');
    }

    public function test_unauthorized_users_cannot_view_or_manage_pipeline(): void
    {
        $candidateUser = $this->createUserWithRole('candidate');
        $interviewer = $this->createUserWithRole('interviewer');
        $application = Application::factory()->create();

        $this->actingAs($candidateUser)
            ->get(route('pipeline.index'))
            ->assertForbidden();
        $this->actingAs($candidateUser)
            ->post(route('pipeline.transition', $application), ['to_stage' => 'screening'])
            ->assertForbidden();
        $this->actingAs($interviewer)
            ->post(route('pipeline.transition', $application), ['to_stage' => 'screening'])
            ->assertForbidden();
    }

    public function test_valid_stage_transition_updates_application(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'applied']);

        $this->actingAs($user)
            ->from(route('applications.show', $application))
            ->post(route('pipeline.transition', $application), [
                'to_stage' => 'screening',
                'note' => 'Minimum requirements confirmed.',
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasNoErrors();

        $this->assertSame('screening', $application->refresh()->current_status);
        $this->assertSame($user->id, $application->updated_by_id);
    }

    public function test_invalid_stage_transition_is_blocked(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'applied']);

        $this->actingAs($user)
            ->from(route('applications.show', $application))
            ->post(route('pipeline.transition', $application), [
                'to_stage' => 'selected',
            ])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors([
                'to_stage' => 'The requested pipeline stage transition is not allowed.',
            ]);

        $this->assertSame('applied', $application->refresh()->current_status);
        $this->assertDatabaseCount('application_stage_histories', 0);
    }

    public function test_stage_change_history_records_actor_time_and_note(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $application = Application::factory()->create(['current_status' => 'shortlisted']);

        $this->actingAs($user)->post(route('pipeline.transition', $application), [
            'to_stage' => 'interview',
            'note' => 'Panel interview approved.',
        ]);

        $history = ApplicationStageHistory::query()->sole();

        $this->assertSame($application->id, $history->application_id);
        $this->assertSame('shortlisted', $history->from_stage);
        $this->assertSame('interview', $history->to_stage);
        $this->assertSame($user->id, $history->changed_by_id);
        $this->assertSame('Panel interview approved.', $history->note);
        $this->assertNotNull($history->changed_at);
    }

    public function test_application_show_displays_pipeline_stage_and_history(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'interview']);
        ApplicationStageHistory::factory()->for($application)->create([
            'from_stage' => 'shortlisted',
            'to_stage' => 'interview',
            'changed_by_id' => $user->id,
            'note' => 'Technical interview requested.',
        ]);

        $this->actingAs($user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSeeText('Pipeline stage')
            ->assertSeeText('Interview')
            ->assertSeeText('Stage history')
            ->assertSeeText('Technical interview requested.')
            ->assertSeeText($user->name);
    }

    public function test_application_edit_status_change_uses_pipeline_service_and_records_history(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'applied']);

        $this->actingAs($user)
            ->put(
                route('applications.update', $application),
                $this->applicationPayload($application, ['current_status' => 'shortlisted']),
            )
            ->assertSessionHasNoErrors();

        $this->assertSame('shortlisted', $application->refresh()->current_status);
        $this->assertDatabaseHas('application_stage_histories', [
            'application_id' => $application->id,
            'from_stage' => 'applied',
            'to_stage' => 'shortlisted',
            'changed_by_id' => $user->id,
        ]);
    }

    public function test_pipeline_board_filters_by_search_and_job_posting(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $matching = Application::factory()->create();
        $matching->candidate->update([
            'first_name' => 'Pipeline',
            'last_name' => 'Match',
            'email' => 'pipeline.match@example.test',
        ]);
        $other = Application::factory()->create();
        $other->candidate->update([
            'first_name' => 'Different',
            'last_name' => 'Candidate',
        ]);

        foreach ([
            ['search' => 'Pipeline Match'],
            ['job_posting_id' => $matching->job_posting_id],
        ] as $filter) {
            $this->actingAs($user)
                ->get(route('pipeline.index', $filter))
                ->assertOk()
                ->assertSeeText('Pipeline Match')
                ->assertDontSeeText('Different Candidate');
        }
    }

    public function test_terminal_stage_has_no_further_pipeline_actions(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'selected']);

        $this->actingAs($user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSeeText('This application is in a terminal pipeline stage.')
            ->assertDontSeeText('Update stage');

        $this->actingAs($user)
            ->post(route('pipeline.transition', $application), ['to_stage' => 'rejected'])
            ->assertSessionHasErrors('to_stage');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function applicationPayload(Application $application, array $overrides = []): array
    {
        return array_replace([
            'candidate_id' => $application->candidate_id,
            'job_posting_id' => $application->job_posting_id,
            'source' => $application->source,
            'applied_date' => $application->applied_date->toDateString(),
            'current_status' => $application->current_status,
            'notes' => $application->notes,
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
