<?php

use App\Http\Controllers\Enfas\V92\ManagementController;
use App\Http\Controllers\Enfas\V92\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/healthz',
    [ManagementController::class,'healthz']
)->middleware('throttle:60,1')->name('v92.healthz');

Route::middleware('auth')->group(function () {
    Route::get(
        '/inicio',
        [WorkspaceController::class,'index']
    )->name('v92.workspace');

    Route::redirect('/disponibilidade','/profissionais')
        ->name('v92.availability');

    Route::get(
        '/relatorios',
        [ManagementController::class,'reports']
    )->name('v92.reports');

    Route::get(
        '/relatorios/exportar',
        [ManagementController::class,'exportReports']
    )->name('v92.reports.export');

    Route::get(
        '/auditoria',
        [ManagementController::class,'audit']
    )->name('v92.audit');

    Route::get(
        '/alertas',
        [ManagementController::class,'alerts']
    )->name('v92.alerts');

    Route::post(
        '/alertas/{id}/resolver',
        [ManagementController::class,'resolveAlert']
    )->name('v92.alerts.resolve');

    Route::get(
        '/configuracoes',
        [ManagementController::class,'settings']
    )->name('v92.settings');

    Route::post(
        '/configuracoes',
        [ManagementController::class,'saveSettings']
    )->name('v92.settings.save');

    Route::get(
        '/sistema/saude',
        [ManagementController::class,'health']
    )->name('v92.health.dashboard');

    Route::redirect('/premium','/inicio');
});
