<?php

namespace App\Services\Enfas;

use App\Models\AppSetting;
use Illuminate\Support\Carbon;

class ConfirmationPolicyService
{
    public function voiceFallbackEnabled(): bool
    {
        return (bool) AppSetting::getValue(
            'confirmation',
            'voice_fallback_enabled',
            true
        );
    }

    public function humanFallbackEnabled(): bool
    {
        return (bool) AppSetting::getValue(
            'confirmation',
            'human_fallback_enabled',
            true
        );
    }

    public function respectConsent(): bool
    {
        return (bool) AppSetting::getValue(
            'confirmation',
            'respect_contact_consent',
            true
        );
    }

    public function voiceEscalationMinutes(): int
    {
        return $this->boundedInt(
            'voice_escalation_minutes',
            120,
            15,
            1440
        );
    }

    public function noWhatsappMinutes(): int
    {
        return $this->boundedInt(
            'voice_no_whatsapp_minutes',
            360,
            30,
            2880
        );
    }

    public function retryMinutes(): int
    {
        return $this->boundedInt(
            'voice_retry_minutes',
            30,
            5,
            1440
        );
    }

    public function maxVoiceAttempts(): int
    {
        return $this->boundedInt(
            'voice_max_attempts',
            3,
            1,
            10
        );
    }

    public function allowedStart(): string
    {
        return $this->timeSetting(
            'voice_allowed_start',
            '08:00'
        );
    }

    public function allowedEnd(): string
    {
        return $this->timeSetting(
            'voice_allowed_end',
            '20:00'
        );
    }

    public function professionalIds(): array
    {
        return $this->idList(
            'voice_professional_ids'
        );
    }

    public function serviceIds(): array
    {
        return $this->idList(
            'voice_service_ids'
        );
    }

    public function allowsAppointment(
        object $appointment
    ): bool {
        return $this->voiceFallbackEnabled()
            && $this->patientContactAllowed(
                $appointment
            )
            && $this->matchesScope(
                $appointment
            )
            && $this->hasVoiceNumber(
                $appointment
            );
    }

    public function patientContactAllowed(
        object $appointment
    ): bool {
        if (! $this->respectConsent()) {
            return true;
        }

        if ((bool) (
            $appointment->do_not_contact
            ?? false
        )) {
            return false;
        }

        if (property_exists(
            $appointment,
            'contact_consent'
        )
            && ! (bool) $appointment->contact_consent) {
            return false;
        }

        return true;
    }

    public function matchesScope(
        object $appointment
    ): bool {
        $professionalIds =
            $this->professionalIds();

        if ($professionalIds !== []
            && ! in_array(
                (int) (
                    $appointment->professional_id
                    ?? 0
                ),
                $professionalIds,
                true
            )) {
            return false;
        }

        $serviceIds =
            $this->serviceIds();

        if ($serviceIds !== []
            && ! in_array(
                (int) (
                    $appointment->service_id
                    ?? 0
                ),
                $serviceIds,
                true
            )) {
            return false;
        }

        return true;
    }

    public function hasVoiceNumber(
        object $appointment
    ): bool {
        $digits = preg_replace(
            '/\D+/',
            '',
            (string) (
                $appointment->patient_phone
                ?? ''
            )
        );

        return in_array(
            strlen($digits),
            [10, 11, 12, 13],
            true
        );
    }

    public function isWithinCallWindow(
        ?Carbon $at = null
    ): bool {
        $at = $at
            ? $at->copy()
            : now();

        [$start, $end] =
            $this->windowFor($at);

        return $at->betweenIncluded(
            $start,
            $end
        );
    }

    public function nextAllowedCallAt(
        ?Carbon $at = null
    ): Carbon {
        $at = $at
            ? $at->copy()
            : now();

        [$start, $end] =
            $this->windowFor($at);

        if ($at->lt($start)) {
            return $start;
        }

        if ($at->lte($end)) {
            return $at;
        }

        return $start->addDay();
    }

    public function summary(): array
    {
        return [
            'voice_fallback_enabled' =>
                $this->voiceFallbackEnabled(),
            'human_fallback_enabled' =>
                $this->humanFallbackEnabled(),
            'respect_contact_consent' =>
                $this->respectConsent(),
            'voice_escalation_minutes' =>
                $this->voiceEscalationMinutes(),
            'voice_no_whatsapp_minutes' =>
                $this->noWhatsappMinutes(),
            'voice_retry_minutes' =>
                $this->retryMinutes(),
            'voice_max_attempts' =>
                $this->maxVoiceAttempts(),
            'voice_allowed_start' =>
                $this->allowedStart(),
            'voice_allowed_end' =>
                $this->allowedEnd(),
            'voice_professional_ids' =>
                $this->professionalIds(),
            'voice_service_ids' =>
                $this->serviceIds(),
        ];
    }

    private function windowFor(
        Carbon $at
    ): array {
        $timezone = config(
            'app.timezone',
            'America/Sao_Paulo'
        );

        $day = $at->copy()
            ->timezone($timezone)
            ->startOfDay();

        [$startHour, $startMinute] =
            array_map(
                'intval',
                explode(
                    ':',
                    $this->allowedStart()
                )
            );

        [$endHour, $endMinute] =
            array_map(
                'intval',
                explode(
                    ':',
                    $this->allowedEnd()
                )
            );

        $start = $day->copy()
            ->setTime(
                $startHour,
                $startMinute
            );

        $end = $day->copy()
            ->setTime(
                $endHour,
                $endMinute
            );

        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    private function boundedInt(
        string $key,
        int $default,
        int $min,
        int $max
    ): int {
        $value = (int) AppSetting::getValue(
            'confirmation',
            $key,
            $default
        );

        return max(
            $min,
            min(
                $max,
                $value
            )
        );
    }

    private function timeSetting(
        string $key,
        string $default
    ): string {
        $value = (string) AppSetting::getValue(
            'confirmation',
            $key,
            $default
        );

        return preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            $value
        )
            ? $value
            : $default;
    }

    private function idList(
        string $key
    ): array {
        $raw = trim(
            (string) AppSetting::getValue(
                'confirmation',
                $key,
                ''
            )
        );

        if ($raw === '') {
            return [];
        }

        return collect(
            preg_split(
                '/[,;\s]+/',
                $raw
            )
        )
            ->map(
                fn ($value) => (int) $value
            )
            ->filter(
                fn ($value) => $value > 0
            )
            ->unique()
            ->values()
            ->all();
    }
}
