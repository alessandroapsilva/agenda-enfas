<?php

use App\Http\Controllers\Enfas\RelationshipHubController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get(
        '/relacionamento',
        [RelationshipHubController::class, 'index']
    )
        ->middleware('permission:agenda.view')
        ->name('relationship.index');
});
