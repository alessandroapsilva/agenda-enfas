<?php

use App\Http\Controllers\Enfas\V10\ConfirmationCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get(
        '/confirmacoes',
        [ConfirmationCenterController::class,'index']
    )->name('v10.confirmations');

    Route::patch(
        '/confirmacoes/{appointment}/status',
        [ConfirmationCenterController::class,'mark']
    )->name('v10.confirmations.mark');

    Route::get(
        '/comunicacoes',
        [ConfirmationCenterController::class,'history']
    )->name('v10.communications');
});
