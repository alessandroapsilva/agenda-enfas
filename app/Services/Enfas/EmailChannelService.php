<?php

namespace App\Services\Enfas;

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

        $safeBrand =
            e($brandName);

        $safeSubject =
            e($subject);

        $safeBody =
            nl2br(
                e($body)
            );

        $logoHtml = $logoUrl
            ? '<img src="'
                .e($logoUrl)
                .'" alt="'
                .$safeBrand
                .'" style="display:block;max-height:52px;max-width:180px;margin:0 0 22px;">'
            : '';

        $html =
            '<div style="margin:0;background:#f5f7fb;padding:32px 16px;font-family:Arial,Helvetica,sans-serif;color:#263244;">'
            .'<div style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e6ebf2;border-radius:16px;padding:30px;box-shadow:0 8px 30px rgba(15,23,42,.05);">'
            .$logoHtml
            .'<div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#718096;margin-bottom:8px;">'
            .$safeBrand
            .'</div>'
            .'<h1 style="margin:0 0 20px;font-size:21px;line-height:1.3;color:#172033;">'
            .$safeSubject
            .'</h1>'
            .'<div style="font-size:14px;line-height:1.65;color:#46556a;">'
            .$safeBody
            .'</div>'
            .'<div style="margin-top:26px;padding-top:18px;border-top:1px solid #edf1f5;font-size:11px;color:#8793a4;">'
            .'Mensagem enviada pelo ENFAS Agenda.'
            .'</div>'
            .'</div>'
            .'</div>';

        Mail::html(
            $html,
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
