<?php

use App\Http\Controllers\Enfas\V6\MetaSubscriptionController;
use App\Http\Controllers\Enfas\V6\WhatsAppAutomationController;
use App\Http\Controllers\Enfas\V6\WhatsAppMessageController;
use App\Http\Controllers\Enfas\V6\Meta\WhatsAppWebhookController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/meta/whatsapp',[WhatsAppWebhookController::class,'verify'])
    ->name('enfas.v6.meta.verify');

Route::post('/webhooks/meta/whatsapp',[WhatsAppWebhookController::class,'receive'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('enfas.v6.meta.receive');

Route::middleware('auth')->group(function(){
    Route::post('/whatsapp/assinar-waba',[MetaSubscriptionController::class,'subscribe'])
        ->middleware('permission:whatsapp.manage')
        ->name('enfas.v6.meta.subscribe');

    // Template management is consolidated in the V9 Template Studio routes.
    // Keep this file focused on Meta webhook/subscription, automations and message history.

    Route::get('/whatsapp/automacoes',[WhatsAppAutomationController::class,'index'])
        ->middleware('permission:whatsapp.manage')
        ->name('enfas.v6.automations');
    Route::post('/whatsapp/automacoes',[WhatsAppAutomationController::class,'store'])
        ->middleware('permission:whatsapp.manage')
        ->name('enfas.v6.automations.store');
    Route::patch('/whatsapp/automacoes/{automation}/status',[WhatsAppAutomationController::class,'toggle'])
        ->middleware('permission:whatsapp.manage')
        ->name('enfas.v6.automations.toggle');
    Route::delete('/whatsapp/automacoes/{automation}',[WhatsAppAutomationController::class,'delete'])
        ->middleware('permission:whatsapp.manage')
        ->name('enfas.v6.automations.delete');

    Route::get('/whatsapp/mensagens',[WhatsAppMessageController::class,'index'])
        ->middleware('permission:whatsapp.view')
        ->name('enfas.v6.messages');
    Route::post('/whatsapp/mensagens/enviar',[WhatsAppMessageController::class,'send'])
        ->middleware('permission:whatsapp.manage')
        ->name('enfas.v6.messages.send');
});
