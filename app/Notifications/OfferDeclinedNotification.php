<?php

namespace App\Notifications;

class OfferDeclinedNotification extends AtsMailNotification
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
            subjectLine: "Offer response recorded - {$jobTitle}",
            heading: 'Offer declined',
            intro: "Your declined offer response for {$companyName} has been recorded.",
            details: ['Position' => $jobTitle, ...$offerDetails],
            outro: 'Thank you for the time you invested in the recruitment process.',
        );
    }
}
