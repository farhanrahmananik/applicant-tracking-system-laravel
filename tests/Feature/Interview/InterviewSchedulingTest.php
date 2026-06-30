<?php

namespace Tests\Feature\Interview;

use App\Models\Application;
use App\Models\InterviewSchedule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_users_can_view_interview_index(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $interviewerViewer = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create();

        foreach ([$recruiter, $interviewerViewer] as $user) {
            $this->actingAs($user)
                ->get(route('interviews.index'))
                ->assertOk()
                ->assertViewIs('interviews.index')
                ->assertSeeText($interview->application->candidate->full_name)
                ->assertSeeText($interview->application->jobPosting->title);
        }
    }

    public function test_authorized_user_can_create_interview_for_active_application(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $interviewer = $this->createUserWithRole('interviewer');
        $application = Application::factory()->create(['current_status' => 'screening']);

        $response = $this->actingAs($recruiter)->post(
            route('interviews.store'),
            $this->validPayload($application, $interviewer, [
                'location' => ' Conference Room A ',
                'notes' => ' Prepare architecture discussion. ',
            ]),
        );

        $interview = InterviewSchedule::query()->firstOrFail();

        $response->assertRedirect(route('interviews.show', $interview));
        $this->assertSame($application->id, $interview->application_id);
        $this->assertSame($interviewer->id, $interview->interviewer_id);
        $this->assertSame($recruiter->id, $interview->created_by_id);
        $this->assertSame($recruiter->id, $interview->updated_by_id);
        $this->assertSame('Conference Room A', $interview->location);
        $this->assertSame('Prepare architecture discussion.', $interview->notes);
    }

    public function test_create_page_preselects_application_from_query_string(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $this->createUserWithRole('interviewer');
        $application = Application::factory()->create(['current_status' => 'shortlisted']);

        $this->actingAs($recruiter)
            ->get(route('interviews.create', ['application_id' => $application->id]))
            ->assertOk()
            ->assertSee('value="'.$application->id.'" selected', false);
    }

    public function test_rejected_and_withdrawn_applications_cannot_be_scheduled(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $interviewer = $this->createUserWithRole('interviewer');

        foreach (Application::TERMINAL_STATUSES as $status) {
            $application = Application::factory()->create(['current_status' => $status]);

            $this->actingAs($recruiter)
                ->from(route('interviews.create'))
                ->post(route('interviews.store'), $this->validPayload($application, $interviewer))
                ->assertRedirect(route('interviews.create'))
                ->assertSessionHasErrors([
                    'application_id' => 'Interviews cannot be scheduled for rejected or withdrawn applications.',
                ]);
        }

        $this->assertDatabaseCount('interview_schedules', 0);
    }

    public function test_authorized_user_can_update_interview(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $interviewer = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create([
            'interviewer_id' => $interviewer->id,
            'type' => 'phone',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($recruiter)->put(
            route('interviews.update', $interview),
            $this->validPayload($interview->application, $interviewer, [
                'type' => 'technical',
                'status' => 'rescheduled',
                'duration_minutes' => 90,
            ]),
        );

        $interview->refresh();

        $response->assertRedirect(route('interviews.show', $interview));
        $this->assertSame('technical', $interview->type);
        $this->assertSame('rescheduled', $interview->status);
        $this->assertSame(90, $interview->duration_minutes);
        $this->assertSame($recruiter->id, $interview->updated_by_id);
    }

    public function test_interview_cannot_be_updated_after_application_becomes_terminal(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $interviewer = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create([
            'interviewer_id' => $interviewer->id,
            'status' => 'scheduled',
        ]);
        $interview->application->update(['current_status' => 'rejected']);

        $this->actingAs($recruiter)
            ->from(route('interviews.edit', $interview))
            ->put(
                route('interviews.update', $interview),
                $this->validPayload($interview->application, $interviewer, ['status' => 'cancelled']),
            )
            ->assertRedirect(route('interviews.edit', $interview))
            ->assertSessionHasErrors('application_id');

        $this->assertSame('scheduled', $interview->refresh()->status);
    }

    public function test_interview_show_displays_schedule_and_badges(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $interview = InterviewSchedule::factory()->create([
            'type' => 'video',
            'status' => 'completed',
            'meeting_link' => 'https://meet.example.test/interview-room',
            'notes' => 'Candidate joined as scheduled.',
        ]);

        $this->actingAs($recruiter)
            ->get(route('interviews.show', $interview))
            ->assertOk()
            ->assertViewIs('interviews.show')
            ->assertSeeText($interview->application->candidate->full_name)
            ->assertSeeText($interview->application->jobPosting->title)
            ->assertSeeText($interview->interviewer->name)
            ->assertSeeText('Video')
            ->assertSeeText('Completed')
            ->assertSee('interview-status-completed', false)
            ->assertSeeText('Candidate joined as scheduled.');
    }

    public function test_interview_appears_on_application_show_page(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $interview = InterviewSchedule::factory()->create([
            'type' => 'technical',
            'status' => 'scheduled',
        ]);

        $this->actingAs($recruiter)
            ->get(route('applications.show', $interview->application))
            ->assertOk()
            ->assertSeeText('Interviews')
            ->assertSeeText($interview->interviewer->name)
            ->assertSeeText('Technical')
            ->assertSeeText('Scheduled')
            ->assertSee(route('interviews.create', ['application_id' => $interview->application_id]));
    }

    public function test_terminal_application_page_does_not_offer_schedule_action(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'withdrawn']);

        $this->actingAs($recruiter)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSeeText('Interviews')
            ->assertDontSee(route('interviews.create', ['application_id' => $application->id]));
    }

    public function test_search_and_filters_narrow_interview_index(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $matching = InterviewSchedule::factory()->create([
            'type' => 'technical',
            'status' => 'rescheduled',
            'scheduled_at' => '2026-07-15 10:00:00',
        ]);
        $matching->application->candidate->update([
            'first_name' => 'Distinctive',
            'last_name' => 'Candidate',
            'email' => 'distinctive.interview@example.test',
        ]);
        $matching->interviewer->update(['name' => 'Specific Interviewer']);

        $other = InterviewSchedule::factory()->create([
            'type' => 'phone',
            'status' => 'completed',
            'scheduled_at' => '2026-05-10 09:00:00',
        ]);
        $other->application->candidate->update([
            'first_name' => 'Unrelated',
            'last_name' => 'Person',
        ]);

        foreach ([
            ['search' => 'Distinctive Candidate'],
            ['search' => $matching->application->jobPosting->title],
            ['search' => 'Specific Interviewer'],
            ['type' => 'technical'],
            ['status' => 'rescheduled'],
            ['date_from' => '2026-07-01', 'date_to' => '2026-07-31'],
        ] as $filter) {
            $this->actingAs($recruiter)
                ->get(route('interviews.index', $filter))
                ->assertOk()
                ->assertSeeText('Distinctive Candidate')
                ->assertDontSeeText('Unrelated Person');
        }
    }

    public function test_validation_rejects_invalid_interview_data(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');

        $this->actingAs($recruiter)
            ->from(route('interviews.create'))
            ->post(route('interviews.store'), [
                'application_id' => 999999,
                'interviewer_id' => 999999,
                'type' => 'panel',
                'status' => 'pending',
                'scheduled_at' => 'not-a-date',
                'duration_minutes' => 10,
                'location' => str_repeat('a', 256),
                'meeting_link' => 'not-a-url',
                'notes' => str_repeat('a', 5001),
            ])
            ->assertRedirect(route('interviews.create'))
            ->assertSessionHasErrors([
                'application_id',
                'interviewer_id',
                'type',
                'status',
                'scheduled_at',
                'duration_minutes',
                'location',
                'meeting_link',
                'notes',
            ]);

        $this->assertDatabaseCount('interview_schedules', 0);
    }

    public function test_active_user_without_an_internal_interviewer_role_cannot_be_assigned(): void
    {
        $recruiter = $this->createUserWithRole('recruiter');
        $candidateUser = $this->createUserWithRole('candidate');
        $application = Application::factory()->create();

        $this->actingAs($recruiter)
            ->from(route('interviews.create'))
            ->post(
                route('interviews.store'),
                $this->validPayload($application, $candidateUser),
            )
            ->assertRedirect(route('interviews.create'))
            ->assertSessionHasErrors('interviewer_id');

        $this->assertDatabaseCount('interview_schedules', 0);
    }

    public function test_permissions_block_unauthorized_actions(): void
    {
        $candidateUser = $this->createUserWithRole('candidate');
        $interviewerUser = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create();

        $this->actingAs($candidateUser)->get(route('interviews.index'))->assertForbidden();
        $this->actingAs($candidateUser)->get(route('interviews.show', $interview))->assertForbidden();

        $this->actingAs($interviewerUser)->get(route('interviews.show', $interview))->assertOk();
        $this->actingAs($interviewerUser)->get(route('interviews.create'))->assertForbidden();
        $this->actingAs($interviewerUser)->post(route('interviews.store'), [])->assertForbidden();
        $this->actingAs($interviewerUser)->get(route('interviews.edit', $interview))->assertForbidden();
        $this->actingAs($interviewerUser)->put(route('interviews.update', $interview), [])->assertForbidden();
        $this->actingAs($interviewerUser)->delete(route('interviews.destroy', $interview))->assertForbidden();
    }

    public function test_hr_manager_can_soft_delete_interview_and_recruiter_cannot(): void
    {
        $hrManager = $this->createUserWithRole('hr_manager');
        $recruiter = $this->createUserWithRole('recruiter');
        $interview = InterviewSchedule::factory()->create();

        $this->actingAs($recruiter)
            ->delete(route('interviews.destroy', $interview))
            ->assertForbidden();

        $this->actingAs($hrManager)
            ->delete(route('interviews.destroy', $interview))
            ->assertRedirect(route('interviews.index'));

        $this->assertSoftDeleted($interview);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        Application $application,
        User $interviewer,
        array $overrides = [],
    ): array {
        return array_replace([
            'application_id' => $application->id,
            'interviewer_id' => $interviewer->id,
            'type' => 'video',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'location' => null,
            'meeting_link' => 'https://meet.example.test/interview',
            'notes' => 'Initial interview schedule.',
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
