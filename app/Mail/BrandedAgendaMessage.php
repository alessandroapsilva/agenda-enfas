<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BrandedAgendaMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $messageSubject,
        public string $messageBody,
        public string $brandName,
        public ?string $logoUrl = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->messageSubject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enfas-message',
            with: [
                'messageBody' =>
                    $this->messageBody,
                'brandName' =>
                    $this->brandName,
                'logoUrl' =>
                    $this->logoUrl,
            ]
        );
    }
}
