<?php

namespace App\Services\Enfas\Voice;

use RuntimeException;

class VoiceProviderManager
{
    public function __construct(
        private readonly TwilioVoiceProvider $twilio
    ) {
    }

    public function driver(): VoiceProvider
    {
        return match (
            config(
                'services.voice.provider',
                'twilio'
            )
        ) {
            'twilio' => $this->twilio,

            default => throw new RuntimeException(
                'Provedor de voz não suportado.'
            ),
        };
    }
}
