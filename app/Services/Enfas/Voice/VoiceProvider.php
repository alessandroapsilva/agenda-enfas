<?php

namespace App\Services\Enfas\Voice;

interface VoiceProvider
{
    public function name(): string;

    public function enabled(): bool;

    public function ready(): bool;

    /**
     * @return array{external_id:string,status:string}
     */
    public function placeConfirmationCall(object $attempt): array;
}
