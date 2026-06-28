<?php

namespace Tests\Feature\Interview;

use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_feedback_create_page(): void
    {
        $user = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create(['status' => 'scheduled']);

        $this->actingAs($user)
            ->get(route('interviews.feedback.create', $interview))
            ->assertOk()
            ->assertViewIs('interview-feedback.create')
            ->assertSeeText($interview->application->candidate->full_name)
            ->assertSeeText($interview->application->jobPosting->title);
    }

    public function test_authorized_user_can_submit_feedback_for_scheduled_and_completed_interviews(): void
    {
        $user = $this->createUserWithRole('interviewer');

        foreach (['scheduled', 'completed'] as $status) {
            $interview = InterviewSchedule::factory()->create(['status' => $status]);

            $response = $this->actingAs($user)->post(
                route('interviews.feedback.store', $interview),
                $this->validPayload([
                    'summary' => ' Clear evidence supports this assessment. ',
                    'strengths' => ' Strong technical communication. ',
                ]),
            );

            $feedback = InterviewFeedback::query()
                ->where('interview_schedule_id', $interview->id)
                ->firstOrFail();

            $response->assertRedirect(route('interview-feedback.show', $feedback));
            $this->assertSame($user->id, $feedback->submitted_by_id);
            $this->assertNotNull($feedback->submitted_at);
            $this->assertSame('Clear evidence supports this assessment.', $feedback->summary);
            $this->assertSame('Strong technical communication.', $feedback->strengths);
        }
    }

    public function test_feedback_appears_on_interview_show_page(): void
    {
        $viewer = $this->createUserWithRole('recruiter');
        $feedback = InterviewFeedback::factory()->create([
            'recommendation' => 'strong_hire',
            'rating' => 5,
            'summary' => 'Excellent evidence across all assessed competencies.',
        ]);

        $this->actingAs($viewer)
            ->get(route('interviews.show', $feedback->interviewSchedule))
            ->assertOk()
            ->assertSeeText('Interview feedback')
            ->assertSeeText($feedback->submittedBy->name)
            ->assertSeeText('Strong Hire')
            ->assertSeeText('5 / 5')
            ->assertSeeText('Excellent evidence across all assessed competencies.');
    }

    public function test_feedback_summary_appears_on_application_show_page(): void
    {
        $viewer = $this->createUserWithRole('recruiter');
        $interview = InterviewSchedule::factory()->create();
        InterviewFeedback::factory()->for($interview, 'interviewSchedule')->create(['rating' => 4]);
        InterviewFeedback::factory()->for($interview, 'interviewSchedule')->create(['rating' => 5]);

        $this->actingAs($viewer)
            ->get(route('applications.show', $interview->application))
            ->assertOk()
            ->assertSeeText('Feedback')
            ->assertSeeText('4.5 / 5')
            ->assertSeeText('2 submitted');
    }

    public function test_validation_rejects_invalid_recommendation(): void
    {
        $user = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create();

        $this->actingAs($user)
            ->from(route('interviews.feedback.create', $interview))
            ->post(
                route('interviews.feedback.store', $interview),
                $this->validPayload(['recommendation' => 'undecided']),
            )
            ->assertRedirect(route('interviews.feedback.create', $interview))
            ->assertSessionHasErrors('recommendation');

        $this->assertDatabaseCount('interview_feedback', 0);
    }

    public function test_validation_rejects_rating_outside_one_to_five(): void
    {
        $user = $this->createUserWithRole('interviewer');

        foreach ([0, 6] as $rating) {
            $interview = InterviewSchedule::factory()->create();

            $this->actingAs($user)
                ->from(route('interviews.feedback.create', $interview))
                ->post(
                    route('interviews.feedback.store', $interview),
                    $this->validPayload(['rating' => $rating]),
                )
                ->assertRedirect(route('interviews.feedback.create', $interview))
                ->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('interview_feedback', 0);
    }

    public function test_cancelled_interview_cannot_receive_feedback(): void
    {
        $user = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create(['status' => 'cancelled']);

        $this->actingAs($user)
            ->from(route('interviews.show', $interview))
            ->post(route('interviews.feedback.store', $interview), $this->validPayload())
            ->assertRedirect(route('interviews.show', $interview))
            ->assertSessionHasErrors([
                'summary' => 'Feedback cannot be submitted for a cancelled interview.',
            ]);

        $this->assertDatabaseCount('interview_feedback', 0);
    }

    public function test_same_user_cannot_submit_duplicate_feedback_for_interview(): void
    {
        $user = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create();
        InterviewFeedback::factory()
            ->for($interview, 'interviewSchedule')
            ->for($user, 'submittedBy')
            ->create();

        $this->actingAs($user)
            ->from(route('interviews.show', $interview))
            ->post(route('interviews.feedback.store', $interview), $this->validPayload())
            ->assertRedirect(route('interviews.show', $interview))
            ->assertSessionHasErrors([
                'summary' => 'You have already submitted feedback for this interview. Edit the existing feedback instead.',
            ]);

        $this->assertDatabaseCount('interview_feedback', 1);
    }

    public function test_different_users_can_submit_feedback_for_same_interview(): void
    {
        $firstUser = $this->createUserWithRole('interviewer');
        $secondUser = $this->createUserWithRole('recruiter');
        $interview = InterviewSchedule::factory()->create();

        $this->actingAs($firstUser)
            ->post(route('interviews.feedback.store', $interview), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->actingAs($secondUser)
            ->post(
                route('interviews.feedback.store', $interview),
                $this->validPayload(['recommendation' => 'maybe', 'rating' => 3]),
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('interview_feedback', 2);
    }

    public function test_authorized_user_can_update_feedback_without_changing_submission_metadata(): void
    {
        $user = $this->createUserWithRole('interviewer');
        $submittedAt = now()->subDay()->startOfSecond();
        $feedback = InterviewFeedback::factory()
            ->for($user, 'submittedBy')
            ->create([
                'summary' => 'Original summary.',
                'recommendation' => 'maybe',
                'rating' => 3,
                'submitted_at' => $submittedAt,
            ]);

        $response = $this->actingAs($user)->put(
            route('interview-feedback.update', $feedback),
            $this->validPayload([
                'summary' => 'Updated evidence-based summary.',
                'recommendation' => 'hire',
                'rating' => 4,
            ]),
        );

        $feedback->refresh();

        $response->assertRedirect(route('interview-feedback.show', $feedback));
        $this->assertSame('Updated evidence-based summary.', $feedback->summary);
        $this->assertSame('hire', $feedback->recommendation);
        $this->assertSame(4, $feedback->rating);
        $this->assertSame($user->id, $feedback->submitted_by_id);
        $this->assertTrue($feedback->submitted_at->equalTo($submittedAt));
    }

    public function test_feedback_cannot_be_updated_after_interview_is_cancelled(): void
    {
        $user = $this->createUserWithRole('interviewer');
        $feedback = InterviewFeedback::factory()->for($user, 'submittedBy')->create();
        $feedback->interviewSchedule->update(['status' => 'cancelled']);

        $this->actingAs($user)
            ->from(route('interview-feedback.edit', $feedback))
            ->put(route('interview-feedback.update', $feedback), $this->validPayload())
            ->assertRedirect(route('interview-feedback.edit', $feedback))
            ->assertSessionHasErrors('summary');
    }

    public function test_unauthorized_user_cannot_create_view_or_update_feedback(): void
    {
        $user = $this->createUserWithRole('candidate');
        $interview = InterviewSchedule::factory()->create();
        $feedback = InterviewFeedback::factory()->for($interview, 'interviewSchedule')->create();

        $this->actingAs($user)
            ->get(route('interviews.feedback.create', $interview))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('interviews.feedback.store', $interview), $this->validPayload())
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('interview-feedback.show', $feedback))
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('interview-feedback.edit', $feedback))
            ->assertForbidden();
        $this->actingAs($user)
            ->put(route('interview-feedback.update', $feedback), $this->validPayload())
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'summary' => 'The candidate demonstrated relevant experience and clear communication.',
            'strengths' => 'Strong technical fundamentals and structured problem solving.',
            'weaknesses' => 'Limited experience with high-volume systems.',
            'recommendation' => 'hire',
            'rating' => 4,
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
