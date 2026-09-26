<?php

use App\Http\Controllers\Enfas\V6\WhatsAppAutomationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::patch(
        '/whatsapp/automacoes/{automation}',
        [WhatsAppAutomationController::class,'update']
    )->name('enfas.v10.automations.update');
});
