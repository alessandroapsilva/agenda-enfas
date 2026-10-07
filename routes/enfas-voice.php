<?php

use App\Http\Controllers\Enfas\V11\TwilioVoiceWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks/voice/twilio')
    ->middleware('throttle:120,1')
    ->group(function () {

        Route::post(
            '/answer/{attempt}',
            [
                TwilioVoiceWebhookController::class,
                'answer',
            ]
        )
            ->whereNumber('attempt')
            ->name('voice.twilio.answer');

        Route::post(
            '/gather/{attempt}',
            [
                TwilioVoiceWebhookController::class,
                'gather',
            ]
        )
            ->whereNumber('attempt')
            ->name('voice.twilio.gather');

        Route::post(
            '/status/{attempt}',
            [
                TwilioVoiceWebhookController::class,
                'status',
            ]
        )
            ->whereNumber('attempt')
            ->name('voice.twilio.status');
    });
