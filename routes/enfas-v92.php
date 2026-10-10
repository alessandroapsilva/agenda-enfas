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
    )->middleware('permission:dashboard.view')->name('v92.workspace');

    Route::redirect('/disponibilidade','/profissionais')
        ->middleware('permission:professionals.view')
        ->name('v92.availability');

    Route::get(
        '/relatorios',
        [ManagementController::class,'reports']
    )->middleware('permission:reports.view')->name('v92.reports');

    Route::get(
        '/relatorios/exportar',
        [ManagementController::class,'exportReports']
    )->middleware('permission:reports.view')->name('v92.reports.export');

    Route::get(
        '/auditoria',
        [ManagementController::class,'audit']
    )->middleware('permission:audit.view')->name('v92.audit');

    Route::get(
        '/alertas',
        [ManagementController::class,'alerts']
    )->middleware('permission:reports.view')->name('v92.alerts');

    Route::post(
        '/alertas/{id}/resolver',
        [ManagementController::class,'resolveAlert']
    )->middleware('permission:settings.manage')->name('v92.alerts.resolve');

    Route::redirect(
        '/configuracoes',
        '/configuracoes/agenda'
    )
        ->middleware('permission:settings.manage')
        ->name('v92.settings');

    Route::get(
        '/sistema/saude',
        [ManagementController::class,'health']
    )->middleware('permission:settings.manage')->name('v92.health.dashboard');

    Route::redirect('/premium','/inicio');
});
