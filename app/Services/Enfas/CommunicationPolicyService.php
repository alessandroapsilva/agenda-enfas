<?php

namespace App\Services\Enfas;

use App\Models\AppSetting;

class CommunicationPolicyService
{
    public function patientEmailEnabled(): bool
    {
        return (bool) AppSetting::getValue(
            'communication',
            'patient_email_enabled',
            false
        );
    }

    public function patientSmsEnabled(): bool
    {
        return (bool) AppSetting::getValue(
            'communication',
            'patient_sms_enabled',
            false
        );
    }

    public function professionalWhatsAppEnabled(): bool
    {
        return (bool) AppSetting::getValue(
            'communication',
            'professional_whatsapp_enabled',
            false
        );
    }

    public function professionalEmailEnabled(): bool
    {
        return (bool) AppSetting::getValue(
            'communication',
            'professional_email_enabled',
            false
        );
    }

    public function professionalSmsEnabled(): bool
    {
        return (bool) AppSetting::getValue(
            'communication',
            'professional_sms_enabled',
            false
        );
    }

    public function patientEventEnabled(string $event): bool
    {
        if (str_starts_with($event, 'reminder')) {
            return (bool) AppSetting::getValue(
                'communication',
                'patient_reminders_enabled',
                true
            );
        }

        return (bool) AppSetting::getValue(
            'communication',
            'patient_'.$event.'_enabled',
            true
        );
    }

    public function professionalEventEnabled(string $event): bool
    {
        return (bool) AppSetting::getValue(
            'communication',
            'professional_'.$event.'_enabled',
            true
        );
    }

    public function summary(): array
    {
        return [
            'patient_email_enabled' =>
                $this->patientEmailEnabled(),
            'patient_sms_enabled' =>
                $this->patientSmsEnabled(),
            'patient_reminders_enabled' =>
                $this->patientEventEnabled('reminder'),
            'patient_created_enabled' =>
                $this->patientEventEnabled('created'),
            'patient_confirmed_enabled' =>
                $this->patientEventEnabled('confirmed'),
            'patient_cancelled_enabled' =>
                $this->patientEventEnabled('cancelled'),
            'patient_rescheduled_enabled' =>
                $this->patientEventEnabled('rescheduled'),
            'professional_whatsapp_enabled' =>
                $this->professionalWhatsAppEnabled(),
            'professional_email_enabled' =>
                $this->professionalEmailEnabled(),
            'professional_sms_enabled' =>
                $this->professionalSmsEnabled(),
            'professional_created_enabled' =>
                $this->professionalEventEnabled('created'),
            'professional_confirmed_enabled' =>
                $this->professionalEventEnabled('confirmed'),
            'professional_cancelled_enabled' =>
                $this->professionalEventEnabled('cancelled'),
            'professional_rescheduled_enabled' =>
                $this->professionalEventEnabled('rescheduled'),
        ];
    }
}
