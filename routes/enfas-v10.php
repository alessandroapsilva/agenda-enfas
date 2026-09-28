<?php

use App\Http\Controllers\Enfas\V10\ConfirmationCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get(
        '/confirmacoes',
        fn () => redirect()->route('v11.confirmations')
    )->middleware('permission:agenda.view')->name('v10.confirmations');

    Route::patch(
        '/confirmacoes/{appointment}/status',
        [ConfirmationCenterController::class,'mark']
    )->middleware('permission:agenda.manage')->name('v10.confirmations.mark');

    Route::get(
        '/comunicacoes',
        [ConfirmationCenterController::class,'history']
    )->middleware('permission:whatsapp.view')->name('v10.communications');
});
