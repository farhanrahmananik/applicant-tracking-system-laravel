<?php

namespace App\Notifications;

class OfferAcceptedNotification extends AtsMailNotification
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
            subjectLine: "Offer acceptance recorded - {$jobTitle}",
            heading: 'Offer accepted',
            intro: "Your accepted offer response for {$companyName} has been recorded.",
            details: ['Position' => $jobTitle, ...$offerDetails],
            outro: 'The recruitment team will contact you with any remaining joining details.',
        );
    }
}
