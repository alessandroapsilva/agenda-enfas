<?php

use App\Http\Controllers\Enfas\HomeController;
use App\Http\Controllers\Enfas\ActivitiesController;
use App\Http\Controllers\Enfas\SettingsController;
use App\Http\Controllers\Enfas\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard',[HomeController::class,'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::get('/atividades',[ActivitiesController::class,'index'])->middleware('permission:activities.view')->name('activities.index');
    Route::post('/atividades',[ActivitiesController::class,'store'])->middleware('permission:activities.manage')->name('activities.store');
    Route::patch('/atividades/{task}/concluir',[ActivitiesController::class,'complete'])->whereNumber('task')->middleware('permission:activities.view')->name('activities.complete');


    Route::get('/whatsapp',[WhatsAppController::class,'index'])->middleware('permission:whatsapp.view')->name('enfas.whatsapp');
    Route::get('/whatsapp/conversas/{conversation}',[WhatsAppController::class,'thread'])->middleware('permission:whatsapp.view')->name('enfas.whatsapp.thread');
    Route::post('/whatsapp/conversas/{conversation}/mensagem',[WhatsAppController::class,'sendConversationMessage'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.thread.send');
    Route::patch('/whatsapp/conversas/{conversation}/assumir',[WhatsAppController::class,'takeover'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.thread.takeover');
    Route::patch('/whatsapp/conversas/{conversation}/robo',[WhatsAppController::class,'release'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.thread.release');
    Route::patch('/whatsapp/conversas/{conversation}/encerrar',[WhatsAppController::class,'close'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.thread.close');
    Route::patch('/whatsapp/conversas/{conversation}/contexto',[WhatsAppController::class,'updateConversationContext'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.thread.context');
    Route::post('/whatsapp/conversas/{conversation}/atividades',[WhatsAppController::class,'createTask'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.thread.tasks.store');
    Route::patch('/whatsapp/atividades/{task}/concluir',[WhatsAppController::class,'completeTask'])->whereNumber('task')->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.tasks.complete');
    Route::post('/whatsapp/respostas-rapidas',[WhatsAppController::class,'storeQuickReply'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.quick-replies.store');

    Route::post('/whatsapp',[WhatsAppController::class,'save'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.save');
    Route::post('/whatsapp/testar',[WhatsAppController::class,'test'])->middleware('permission:whatsapp.manage')->name('enfas.whatsapp.test');

    Route::get('/configuracoes/aparencia',[SettingsController::class,'branding'])->middleware('permission:settings.manage')->name('enfas.branding');
    Route::post('/configuracoes/aparencia',[SettingsController::class,'saveBranding'])->middleware('permission:settings.manage')->name('enfas.branding.save');
    Route::get('/configuracoes/agenda',[SettingsController::class,'agenda'])->middleware('permission:settings.manage')->name('enfas.agenda-settings');
    Route::post('/configuracoes/agenda',[SettingsController::class,'saveAgenda'])->middleware('permission:settings.manage')->name('enfas.agenda-settings.save');
});
