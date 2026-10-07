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
        )->middleware('permission:agenda.view')->name('v11.today');

        Route::get(
            '/confirmacoes/central',
            [
                OperationsController::class,
                'confirmations',
            ]
        )->middleware('permission:agenda.view')->name(
            'v11.confirmations'
        );

        Route::patch(
            '/confirmacoes/central/{appointment}/ligacao',
            [
                OperationsController::class,
                'recordCall',
            ]
        )->whereNumber(
            'appointment'
        )->middleware('permission:agenda.manage')->name(
            'v11.confirmations.call'
        );

        Route::patch(
            '/confirmacoes/central/{appointment}',
            [
                OperationsController::class,
                'mark',
            ]
        )->whereNumber(
            'appointment'
        )->middleware('permission:agenda.manage')->name(
            'v11.confirmations.mark'
        );
    });
