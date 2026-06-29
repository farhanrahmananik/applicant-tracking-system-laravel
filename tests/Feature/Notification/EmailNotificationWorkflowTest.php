<?php

namespace Tests\Feature\Notification;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\InterviewSchedule;
use App\Models\JobPosting;
use App\Models\Offer;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ApplicationCreatedNotification;
use App\Notifications\InterviewCancelledNotification;
use App\Notifications\InterviewScheduledNotification;
use App\Notifications\InterviewUpdatedNotification;
use App\Notifications\OfferAcceptedNotification;
use App\Notifications\OfferDeclinedNotification;
use App\Notifications\OfferSentNotification;
use App\Services\ApplicationService;
use App\Services\EmailNotificationService;
use App\Services\InterviewScheduleService;
use App\Services\OfferService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_created_notification_is_dispatched_to_candidate(): void
    {
        Notification::fake();
        $actor = User::factory()->create();
        $candidate = Candidate::factory()->create([
            'first_name' => 'Avery',
            'last_name' => 'Stone',
            'email' => 'avery.stone@example.test',
        ]);
        $jobPosting = JobPosting::factory()->create(['title' => 'Platform Engineer']);

        $this->actingAs($actor);
        app(ApplicationService::class)->create([
            'candidate_id' => $candidate->id,
            'job_posting_id' => $jobPosting->id,
            'source' => 'career_site',
            'applied_date' => now()->toDateString(),
            'current_status' => 'applied',
            'notes' => null,
        ]);

        $this->assertOnDemandNotification(
            ApplicationCreatedNotification::class,
            $candidate,
            fn ($notification): bool => $notification->details['Position'] === 'Platform Engineer',
        );
    }

    public function test_interview_scheduled_notification_is_dispatched(): void
    {
        Notification::fake();
        $actor = $this->createUserWithRole('recruiter');
        $interviewer = $this->createUserWithRole('interviewer');
        $application = Application::factory()->create();

        $this->actingAs($actor);
        app(InterviewScheduleService::class)->create(
            $this->interviewPayload($application, $interviewer),
        );

        $this->assertOnDemandNotification(
            InterviewScheduledNotification::class,
            $application->candidate,
            fn ($notification): bool => $notification->details['Interview type'] === 'Video',
        );
    }

    public function test_interview_updated_notification_is_dispatched(): void
    {
        Notification::fake();
        $actor = $this->createUserWithRole('recruiter');
        $interviewer = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create([
            'interviewer_id' => $interviewer->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($actor);
        app(InterviewScheduleService::class)->update(
            $interview,
            $this->interviewPayload($interview->application, $interviewer, [
                'status' => 'rescheduled',
                'location' => 'Conference Room B',
            ]),
        );

        $this->assertOnDemandNotification(
            InterviewUpdatedNotification::class,
            $interview->application->candidate,
            fn ($notification): bool => $notification->details['Location'] === 'Conference Room B',
        );
    }

    public function test_interview_cancelled_notification_is_dispatched_instead_of_updated(): void
    {
        Notification::fake();
        $actor = $this->createUserWithRole('recruiter');
        $interviewer = $this->createUserWithRole('interviewer');
        $interview = InterviewSchedule::factory()->create([
            'interviewer_id' => $interviewer->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($actor);
        app(InterviewScheduleService::class)->update(
            $interview,
            $this->interviewPayload($interview->application, $interviewer, [
                'status' => 'cancelled',
            ]),
        );

        $this->assertOnDemandNotification(
            InterviewCancelledNotification::class,
            $interview->application->candidate,
        );
        Notification::assertNotSentTo(new AnonymousNotifiable, InterviewUpdatedNotification::class);
    }

    public function test_offer_sent_notification_is_dispatched(): void
    {
        Notification::fake();
        $actor = $this->createUserWithRole('recruiter');
        $offer = Offer::factory()->create([
            'status' => 'draft',
            'offer_title' => 'Platform Engineering Offer',
        ]);

        $this->actingAs($actor);
        app(OfferService::class)->transition($offer, 'sent');

        $this->assertOnDemandNotification(
            OfferSentNotification::class,
            $offer->application->candidate,
            fn ($notification): bool => $notification->details['Offer title'] === 'Platform Engineering Offer',
        );
    }

    public function test_offer_accepted_and_declined_notifications_are_dispatched(): void
    {
        Notification::fake();
        $actor = $this->createUserWithRole('recruiter');
        $acceptedOffer = Offer::factory()->sent()->create();
        $declinedOffer = Offer::factory()->sent()->create();

        $this->actingAs($actor);
        app(OfferService::class)->transition($acceptedOffer, 'accepted');
        app(OfferService::class)->transition($declinedOffer, 'declined');

        $this->assertOnDemandNotification(
            OfferAcceptedNotification::class,
            $acceptedOffer->application->candidate,
        );
        $this->assertOnDemandNotification(
            OfferDeclinedNotification::class,
            $declinedOffer->application->candidate,
        );
    }

    public function test_notifications_are_skipped_for_missing_or_invalid_candidate_email(): void
    {
        Notification::fake();

        foreach ([' ', 'not-an-email'] as $email) {
            $application = Application::factory()->create([
                'candidate_id' => Candidate::factory()->create(['email' => $email]),
            ]);

            app(EmailNotificationService::class)->applicationCreated($application);
        }

        Notification::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function interviewPayload(
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
            'notes' => null,
        ], $overrides);
    }

    /**
     * @param  class-string  $notificationClass
     * @param  (callable(object): bool)|null  $assertion
     */
    private function assertOnDemandNotification(
        string $notificationClass,
        Candidate $candidate,
        ?callable $assertion = null,
    ): void {
        Notification::assertSentOnDemand(
            $notificationClass,
            function ($notification, array $channels, AnonymousNotifiable $notifiable) use (
                $candidate,
                $assertion,
            ): bool {
                return $notification instanceof ShouldQueue
                    && $channels === ['mail']
                    && $notifiable->routes['mail'] === [$candidate->email => $candidate->full_name]
                    && ($assertion === null || $assertion($notification));
            },
        );
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
