<?php

use App\Http\Controllers\Enfas\HomeController;
use App\Http\Controllers\Enfas\ModuleController;
use App\Http\Controllers\Enfas\SettingsController;
use App\Http\Controllers\Enfas\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard',[HomeController::class,'index'])->name('dashboard');

    Route::get('/pacientes',[ModuleController::class,'patients'])->name('enfas.patients');
    Route::get('/profissionais',[ModuleController::class,'professionals'])->name('enfas.professionals');
    Route::get('/servicos',[ModuleController::class,'services'])->name('enfas.services');
    Route::get('/agendamentos',[ModuleController::class,'appointments'])->name('enfas.appointments');
    Route::get('/locais',[ModuleController::class,'locations'])->name('enfas.locations');
    Route::get('/disponibilidade',[ModuleController::class,'availability'])->name('enfas.availability');
    Route::get('/campos-personalizados',[ModuleController::class,'customFields'])->name('enfas.custom-fields');
    Route::get('/relatorios',[ModuleController::class,'reports'])->name('enfas.reports');
    Route::get('/alertas',[ModuleController::class,'alerts'])->name('enfas.alerts');
    Route::get('/auditoria',[ModuleController::class,'audit'])->name('enfas.audit');

    Route::get('/whatsapp',[WhatsAppController::class,'index'])->name('enfas.whatsapp');
    Route::post('/whatsapp',[WhatsAppController::class,'save'])->name('enfas.whatsapp.save');
    Route::post('/whatsapp/testar',[WhatsAppController::class,'test'])->name('enfas.whatsapp.test');
    Route::get('/whatsapp/templates',[WhatsAppController::class,'templates'])->name('enfas.whatsapp.templates');
    Route::get('/whatsapp/automacoes',[WhatsAppController::class,'automations'])->name('enfas.whatsapp.automations');
    Route::get('/whatsapp/mensagens',[WhatsAppController::class,'messages'])->name('enfas.whatsapp.messages');

    Route::get('/configuracoes/aparencia',[SettingsController::class,'branding'])->name('enfas.branding');
    Route::post('/configuracoes/aparencia',[SettingsController::class,'saveBranding'])->name('enfas.branding.save');
    Route::get('/configuracoes/agenda',[SettingsController::class,'agenda'])->name('enfas.agenda-settings');
    Route::post('/configuracoes/agenda',[SettingsController::class,'saveAgenda'])->name('enfas.agenda-settings.save');
});
