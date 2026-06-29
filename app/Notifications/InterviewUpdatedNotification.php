<?php

namespace App\Notifications;

class InterviewUpdatedNotification extends AtsMailNotification
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
            subjectLine: "Interview updated - {$jobTitle}",
            heading: 'Interview details updated',
            intro: "The interview details for your application with {$companyName} have changed.",
            details: ['Position' => $jobTitle, ...$interviewDetails],
            outro: 'Please use these updated details for your interview.',
        );
    }
}
