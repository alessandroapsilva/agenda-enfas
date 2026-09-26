<?php

use App\Http\Controllers\Enfas\V6\MetaSubscriptionController;
use App\Http\Controllers\Enfas\V6\WhatsAppAutomationController;
use App\Http\Controllers\Enfas\V6\WhatsAppMessageController;
use App\Http\Controllers\Enfas\V6\WhatsAppTemplateController;
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
        ->name('enfas.v6.meta.subscribe');

    Route::get('/whatsapp/templates',[WhatsAppTemplateController::class,'index'])
        ->name('enfas.v6.templates');
    Route::post('/whatsapp/templates',[WhatsAppTemplateController::class,'store'])
        ->name('enfas.v6.templates.store');
    Route::post('/whatsapp/templates/sincronizar',[WhatsAppTemplateController::class,'sync'])
        ->name('enfas.v6.templates.sync');
    Route::post('/whatsapp/templates/{template}/enviar-meta',[WhatsAppTemplateController::class,'submit'])
        ->name('enfas.v6.templates.submit');
    Route::delete('/whatsapp/templates/{template}',[WhatsAppTemplateController::class,'delete'])
        ->name('enfas.v6.templates.delete');

    Route::get('/whatsapp/automacoes',[WhatsAppAutomationController::class,'index'])
        ->name('enfas.v6.automations');
    Route::post('/whatsapp/automacoes',[WhatsAppAutomationController::class,'store'])
        ->name('enfas.v6.automations.store');
    Route::patch('/whatsapp/automacoes/{automation}/status',[WhatsAppAutomationController::class,'toggle'])
        ->name('enfas.v6.automations.toggle');
    Route::delete('/whatsapp/automacoes/{automation}',[WhatsAppAutomationController::class,'delete'])
        ->name('enfas.v6.automations.delete');

    Route::get('/whatsapp/mensagens',[WhatsAppMessageController::class,'index'])
        ->name('enfas.v6.messages');
    Route::post('/whatsapp/mensagens/enviar',[WhatsAppMessageController::class,'send'])
        ->name('enfas.v6.messages.send');
});
