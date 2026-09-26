<?php
use App\Http\Controllers\Enfas\V8\PremiumController;
use App\Http\Controllers\Enfas\V8\TemplatePremiumController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('premium')->group(function(){
    Route::get('/',[PremiumController::class,'dashboard'])->name('v8.dashboard');

    Route::get('/profissionais',[PremiumController::class,'professionals'])->name('v8.professionals');
    Route::get('/pacientes',[PremiumController::class,'patients'])->name('v8.patients');
    Route::get('/servicos',[PremiumController::class,'services'])->name('v8.services');
    Route::post('/cadastros/{table}',[PremiumController::class,'storeMaster'])->name('v8.master.store');
    Route::patch('/cadastros/{table}/{id}',[PremiumController::class,'updateMaster'])->name('v8.master.update');
    Route::patch('/cadastros/{table}/{id}/status',[PremiumController::class,'toggleMaster'])->name('v8.master.toggle');

    Route::get('/unidades',[PremiumController::class,'locations'])->name('v8.locations');
    Route::post('/unidades',[PremiumController::class,'storeLocation'])->name('v8.locations.store');
    Route::patch('/unidades/{id}/status',[PremiumController::class,'toggleLocation'])->name('v8.locations.toggle');

    Route::get('/disponibilidade',[PremiumController::class,'availability'])->name('v8.availability');
    Route::post('/disponibilidade',[PremiumController::class,'storeAvailability'])->name('v8.availability.store');
    Route::delete('/disponibilidade/{id}',[PremiumController::class,'deleteAvailability'])->name('v8.availability.delete');
    Route::post('/ausencias',[PremiumController::class,'storeAbsence'])->name('v8.absences.store');
    Route::delete('/ausencias/{id}',[PremiumController::class,'deleteAbsence'])->name('v8.absences.delete');

    Route::get('/whatsapp/midia',[PremiumController::class,'media'])->name('v8.media');
    Route::post('/whatsapp/midia',[PremiumController::class,'storeMedia'])->name('v8.media.store');
    Route::patch('/whatsapp/midia/{id}/status',[PremiumController::class,'toggleMedia'])->name('v8.media.toggle');
    Route::delete('/whatsapp/midia/{id}',[PremiumController::class,'deleteMedia'])->name('v8.media.delete');

    Route::get('/whatsapp/modelos',[TemplatePremiumController::class,'index'])->name('v8.templates');
    
    Route::patch('/whatsapp/modelos/{template}/editar',[TemplatePremiumController::class,'update'])->name('v8.templates.update');
    Route::post('/whatsapp/modelos/{template}/duplicar',[TemplatePremiumController::class,'duplicate'])->name('v8.templates.duplicate');
    Route::post('/whatsapp/modelos/{template}/recuperar-categoria',[TemplatePremiumController::class,'recoverCategory'])->name('v8.templates.recover-category');
    Route::post('/whatsapp/modelos/{template}/enviar',[TemplatePremiumController::class,'send'])->name('v8.templates.send');

    Route::patch('/whatsapp/modelos/{template}/status',[PremiumController::class,'toggleTemplate'])->name('v8.templates.toggle');
    Route::post('/whatsapp/modelos/{template}/corrigir',[PremiumController::class,'autoFixTemplate'])->name('v8.templates.autofix');
    Route::post('/whatsapp/modelos/{template}/arquivar',[PremiumController::class,'archiveTemplate'])->name('v8.templates.archive');

    Route::get('/relatorios',[PremiumController::class,'reports'])->name('v8.reports');
    Route::get('/auditoria',[PremiumController::class,'audit'])->name('v8.audit');
    Route::get('/configuracoes',[PremiumController::class,'settings'])->name('v8.settings');
    Route::post('/configuracoes',[PremiumController::class,'saveSettings'])->name('v8.settings.save');
});
