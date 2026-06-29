<?php

namespace App\Notifications;

class InterviewCancelledNotification extends AtsMailNotification
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
            subjectLine: "Interview cancelled - {$jobTitle}",
            heading: 'Interview cancelled',
            intro: "The interview for your application with {$companyName} has been cancelled.",
            details: ['Position' => $jobTitle, ...$interviewDetails],
            outro: 'The recruitment team will contact you if another interview is arranged.',
        );
    }
}
