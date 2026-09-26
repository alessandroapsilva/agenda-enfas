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

    Route::get(
        '/disponibilidade',
        [ManagementController::class,'availability']
    )->name('v92.availability');

    Route::post(
        '/disponibilidade',
        [ManagementController::class,'storeAvailability']
    )->name('v92.availability.store');

    Route::delete(
        '/disponibilidade/{id}',
        [ManagementController::class,'deleteAvailability']
    )->name('v92.availability.delete');

    Route::post(
        '/ausencias',
        [ManagementController::class,'storeAbsence']
    )->name('v92.absences.store');

    Route::delete(
        '/ausencias/{id}',
        [ManagementController::class,'deleteAbsence']
    )->name('v92.absences.delete');

    Route::get(
        '/relatorios',
        [ManagementController::class,'reports']
    )->name('v92.reports');

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
