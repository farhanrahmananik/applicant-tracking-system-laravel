<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\InterviewSchedule;
use App\Models\Offer;
use App\Notifications\ApplicationCreatedNotification;
use App\Notifications\InterviewCancelledNotification;
use App\Notifications\InterviewScheduledNotification;
use App\Notifications\InterviewUpdatedNotification;
use App\Notifications\OfferAcceptedNotification;
use App\Notifications\OfferDeclinedNotification;
use App\Notifications\OfferSentNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

class EmailNotificationService
{
    public function applicationCreated(Application $application): void
    {
        $application->loadMissing(['candidate', 'jobPosting.company']);

        $this->notifyCandidate(
            $application->candidate,
            new ApplicationCreatedNotification(
                candidateName: $application->candidate->full_name,
                jobTitle: $application->jobPosting->title,
                companyName: $application->jobPosting->company->name,
                appliedDate: $application->applied_date->format('M j, Y'),
            ),
        );
    }

    public function interviewScheduled(InterviewSchedule $interview): void
    {
        $interview->loadMissing(['application.candidate', 'application.jobPosting.company']);

        $this->notifyCandidate(
            $interview->application->candidate,
            new InterviewScheduledNotification(
                candidateName: $interview->application->candidate->full_name,
                jobTitle: $interview->application->jobPosting->title,
                companyName: $interview->application->jobPosting->company->name,
                interviewDetails: $this->interviewDetails($interview),
            ),
        );
    }

    public function interviewUpdated(InterviewSchedule $interview): void
    {
        $interview->loadMissing(['application.candidate', 'application.jobPosting.company']);

        $this->notifyCandidate(
            $interview->application->candidate,
            new InterviewUpdatedNotification(
                candidateName: $interview->application->candidate->full_name,
                jobTitle: $interview->application->jobPosting->title,
                companyName: $interview->application->jobPosting->company->name,
                interviewDetails: $this->interviewDetails($interview),
            ),
        );
    }

    public function interviewCancelled(InterviewSchedule $interview): void
    {
        $interview->loadMissing(['application.candidate', 'application.jobPosting.company']);

        $this->notifyCandidate(
            $interview->application->candidate,
            new InterviewCancelledNotification(
                candidateName: $interview->application->candidate->full_name,
                jobTitle: $interview->application->jobPosting->title,
                companyName: $interview->application->jobPosting->company->name,
                interviewDetails: $this->interviewDetails($interview),
            ),
        );
    }

    public function offerSent(Offer $offer): void
    {
        $this->notifyOfferCandidate($offer, OfferSentNotification::class);
    }

    public function offerAccepted(Offer $offer): void
    {
        $this->notifyOfferCandidate($offer, OfferAcceptedNotification::class);
    }

    public function offerDeclined(Offer $offer): void
    {
        $this->notifyOfferCandidate($offer, OfferDeclinedNotification::class);
    }

    /**
     * @return array<string, string>
     */
    private function interviewDetails(InterviewSchedule $interview): array
    {
        $details = [
            'Date and time' => $interview->scheduled_at
                ->timezone(config('app.timezone'))
                ->format('M j, Y H:i T'),
            'Interview type' => Str::headline($interview->type),
            'Duration' => "{$interview->duration_minutes} minutes",
            'Location' => $interview->location ?: 'Details will be provided by the recruitment team',
        ];

        if ($interview->meeting_link) {
            $details['Meeting link'] = $interview->meeting_link;
        }

        return $details;
    }

    /**
     * @param  class-string<OfferSentNotification|OfferAcceptedNotification|OfferDeclinedNotification>  $notificationClass
     */
    private function notifyOfferCandidate(Offer $offer, string $notificationClass): void
    {
        $offer->loadMissing(['application.candidate', 'application.jobPosting.company']);
        $candidate = $offer->application->candidate;

        $this->notifyCandidate(
            $candidate,
            new $notificationClass(
                candidateName: $candidate->full_name,
                jobTitle: $offer->application->jobPosting->title,
                companyName: $offer->application->jobPosting->company->name,
                offerDetails: $this->offerDetails($offer),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    private function offerDetails(Offer $offer): array
    {
        $details = [
            'Offer title' => $offer->offer_title,
            'Compensation' => $offer->currency.' '.number_format((float) $offer->salary_amount, 2),
            'Employment type' => Str::headline($offer->employment_type),
            'Offer expiry' => $offer->expiry_date->format('M j, Y'),
        ];

        if ($offer->expected_joining_date) {
            $details['Expected joining date'] = $offer->expected_joining_date->format('M j, Y');
        }

        return $details;
    }

    private function notifyCandidate(Candidate $candidate, Notification $notification): void
    {
        $email = trim((string) $candidate->email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        NotificationFacade::route('mail', [$email => $candidate->full_name])
            ->notify($notification);
    }
}
