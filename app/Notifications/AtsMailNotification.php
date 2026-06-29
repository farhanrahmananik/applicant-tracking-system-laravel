<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class AtsMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $details
     */
    public function __construct(
        public readonly string $recipientName,
        public readonly string $subjectLine,
        public readonly string $heading,
        public readonly string $intro,
        public readonly array $details,
        public readonly string $outro,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine)
            ->view('emails.ats-notification', [
                'recipientName' => $this->recipientName,
                'heading' => $this->heading,
                'intro' => $this->intro,
                'details' => $this->details,
                'outro' => $this->outro,
            ]);
    }
}
