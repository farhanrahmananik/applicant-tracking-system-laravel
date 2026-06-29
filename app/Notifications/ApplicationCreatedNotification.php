<?php

namespace App\Notifications;

class ApplicationCreatedNotification extends AtsMailNotification
{
    public function __construct(
        string $candidateName,
        string $jobTitle,
        string $companyName,
        string $appliedDate,
    ) {
        parent::__construct(
            recipientName: $candidateName,
            subjectLine: "Application received - {$jobTitle}",
            heading: 'Application received',
            intro: 'Your application has been recorded in our recruitment system.',
            details: [
                'Position' => $jobTitle,
                'Company' => $companyName,
                'Application date' => $appliedDate,
            ],
            outro: 'The recruitment team will contact you if further information or a next step is required.',
        );
    }
}
