<?php

namespace App\Services\Enfas;

use App\Mail\BrandedAgendaMessage;
use App\Models\AppSetting;
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

        $brandName = (string) (
            AppSetting::getValue(
                'branding',
                'organization_name',
                ''
            )
            ?: AppSetting::getValue(
                'branding',
                'system_name',
                'ENFAS Agenda'
            )
        );

        $logo = trim(
            (string) AppSetting::getValue(
                'branding',
                'logo',
                ''
            )
        );

        $logoUrl = $logo !== ''
            ? asset('storage/'.$logo)
            : null;

        Mail::to($to)->send(
            new BrandedAgendaMessage(
                messageSubject: $subject,
                messageBody: $body,
                brandName: $brandName,
                logoUrl: $logoUrl
            )
        );
    }
}
