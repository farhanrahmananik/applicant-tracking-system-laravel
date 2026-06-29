<?php

namespace App\Notifications;

class OfferSentNotification extends AtsMailNotification
{
    /**
     * @param  array<string, string>  $offerDetails
     */
    public function __construct(
        string $candidateName,
        string $jobTitle,
        string $companyName,
        array $offerDetails,
    ) {
        parent::__construct(
            recipientName: $candidateName,
            subjectLine: "Employment offer - {$jobTitle}",
            heading: 'Employment offer sent',
            intro: "An employment offer has been prepared for your application with {$companyName}.",
            details: ['Position' => $jobTitle, ...$offerDetails],
            outro: 'Please review the offer details provided by the recruitment team before the expiry date.',
        );
    }
}
