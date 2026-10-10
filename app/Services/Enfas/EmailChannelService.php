<?php

namespace App\Services\Enfas;

use Illuminate\Support\Facades\Mail;
use RuntimeException;

class EmailChannelService
{
    public function configured(): bool
    {
        $mailer = (string) config(
            'mail.default',
            'log'
        );

        $from = (string) config(
            'mail.from.address',
            ''
        );

        return ! in_array(
            $mailer,
            ['log', 'array'],
            true
        )
            && $from !== ''
            && $from !== 'hello@example.com';
    }

    public function send(
        string $to,
        string $subject,
        string $body
    ): void {
        if (! $this->configured()) {
            throw new RuntimeException(
                'Canal de e-mail ainda não está configurado no servidor.'
            );
        }

        Mail::raw(
            $body,
            function ($message) use (
                $to,
                $subject
            ) {
                $message
                    ->to($to)
                    ->subject($subject);
            }
        );
    }
}
