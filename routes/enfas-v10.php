<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get(
        '/confirmacoes',
        fn () => redirect()->route('v11.confirmations')
    )->middleware('permission:agenda.view')->name('v10.confirmations');

    Route::get(
        '/comunicacoes',
        fn () => redirect()->route('enfas.v6.messages')
    )->middleware('permission:whatsapp.view')->name('v10.communications');
});
