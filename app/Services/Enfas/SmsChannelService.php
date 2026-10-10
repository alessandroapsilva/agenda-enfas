<?php

namespace App\Services\Enfas;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsChannelService
{
    public function configured(): bool
    {
        return (bool) config(
            'services.sms.enabled',
            false
        )
            && filled(
                config(
                    'services.sms.twilio.account_sid'
                )
            )
            && filled(
                config(
                    'services.sms.twilio.auth_token'
                )
            )
            && filled(
                config(
                    'services.sms.twilio.from'
                )
            );
    }

    public function send(
        string $to,
        string $body
    ): ?string {
        if (! $this->configured()) {
            throw new RuntimeException(
                'Canal SMS ainda não está configurado no servidor.'
            );
        }

        $sid = (string) config(
            'services.sms.twilio.account_sid'
        );

        $token = (string) config(
            'services.sms.twilio.auth_token'
        );

        $from = (string) config(
            'services.sms.twilio.from'
        );

        $recipient =
            $this->normalizeBrazilianPhone($to);

        $response = Http::withBasicAuth(
            $sid,
            $token
        )
            ->asForm()
            ->timeout(20)
            ->post(
                'https://api.twilio.com/2010-04-01/Accounts/'
                .$sid
                .'/Messages.json',
                [
                    'From' => $from,
                    'To' => $recipient,
                    'Body' => $body,
                ]
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha ao enviar SMS: '
                .$response->status()
                .' '
                .$response->body()
            );
        }

        return $response->json('sid');
    }

    private function normalizeBrazilianPhone(
        string $phone
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            $phone
        );

        if (strlen($digits) === 10
            || strlen($digits) === 11) {
            $digits = '55'.$digits;
        }

        if (
            ! in_array(
                strlen($digits),
                [12, 13],
                true
            )
            || ! str_starts_with(
                $digits,
                '55'
            )
        ) {
            throw new RuntimeException(
                'Telefone inválido para SMS.'
            );
        }

        return '+'.$digits;
    }
}
