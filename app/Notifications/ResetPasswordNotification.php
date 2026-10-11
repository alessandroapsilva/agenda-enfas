<?php

namespace App\Notifications;

use App\Models\AppSetting;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $brand = (string) (
            AppSetting::getValue('branding', 'organization_name', '')
            ?: AppSetting::getValue('branding', 'system_name', 'ENFAS Agenda')
        );

        $url = route('password.reset.form', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Redefinição de senha · '.$brand)
            ->greeting('Olá, '.$notifiable->name)
            ->line('Recebemos uma solicitação para redefinir a senha do seu acesso ao '.$brand.'.')
            ->action('Redefinir minha senha', $url)
            ->line('Este link expira em '.config('auth.passwords.users.expire').' minutos.')
            ->line('Se você não solicitou a alteração, ignore este e-mail.');
    }
}
