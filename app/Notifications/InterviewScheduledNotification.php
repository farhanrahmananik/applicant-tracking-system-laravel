<?php

namespace App\Notifications;

class InterviewScheduledNotification extends AtsMailNotification
{
    /**
     * @param  array<string, string>  $interviewDetails
     */
    public function __construct(
        string $candidateName,
        string $jobTitle,
        string $companyName,
        array $interviewDetails,
    ) {
        parent::__construct(
            recipientName: $candidateName,
            subjectLine: "Interview scheduled - {$jobTitle}",
            heading: 'Interview scheduled',
            intro: "An interview has been scheduled for your application with {$companyName}.",
            details: ['Position' => $jobTitle, ...$interviewDetails],
            outro: 'Please keep these details available and contact the recruitment team if you need clarification.',
        );
    }
}
