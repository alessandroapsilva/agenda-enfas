<?php

use App\Http\Controllers\Enfas\V11\OperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->group(function () {

        Route::get(
            '/hoje',
            [
                OperationsController::class,
                'today',
            ]
        )->name('v11.today');

        Route::get(
            '/confirmacoes/central',
            [
                OperationsController::class,
                'confirmations',
            ]
        )->name(
            'v11.confirmations'
        );

        Route::patch(
            '/confirmacoes/central/{appointment}',
            [
                OperationsController::class,
                'mark',
            ]
        )->whereNumber(
            'appointment'
        )->name(
            'v11.confirmations.mark'
        );
    });
