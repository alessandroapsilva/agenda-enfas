<?php

use App\Http\Controllers\Enfas\V91\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get(
        '/inicio',
        [WorkspaceController::class,'index']
    )->name('v91.workspace');

    Route::redirect(
        '/premium',
        '/inicio'
    );
});
