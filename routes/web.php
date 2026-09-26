<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/* ENFAS Agenda V11.1 */
require __DIR__.'/enfas-v11-ui.php';


/*
|--------------------------------------------------------------------------
| AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get(
        '/login',
        [AuthController::class, 'showLogin']
    )->name('login');

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )
        ->middleware('throttle:5,1')
        ->name('login.submit');
});


/*
|--------------------------------------------------------------------------
| SISTEMA INTERNO
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/',
        fn () => redirect()->route('dashboard')
    );


    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | AGENDA
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/agenda',
        [AgendaController::class, 'index']
    )->name('agenda.index');

    Route::get(
        '/agenda/eventos',
        [AgendaController::class, 'events']
    )->name('agenda.events');

    Route::patch(
        '/agenda/agendamentos/{appointment}/mover',
        [AgendaController::class, 'move']
    )->name('agenda.move');


    /*
    |--------------------------------------------------------------------------
    | AGENDAMENTOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/agendamentos',
        [AppointmentController::class, 'index']
    )->name('appointments.index');

    Route::post(
        '/agendamentos',
        [AppointmentController::class, 'store']
    )->name('appointments.store');

    Route::get(
        '/agendamentos/{appointment}',
        [AppointmentController::class, 'show']
    )->name('appointments.show');

    Route::patch(
        '/agendamentos/{appointment}/status',
        [AppointmentController::class, 'status']
    )->name('appointments.status');


    /*
    |--------------------------------------------------------------------------
    | PACIENTES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pacientes',
        [PatientController::class, 'index']
    )->name('patients.index');

    Route::post(
        '/pacientes',
        [PatientController::class, 'store']
    )->name('patients.store');


    /*
    |--------------------------------------------------------------------------
    | PROFISSIONAIS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profissionais',
        [ProfessionalController::class, 'index']
    )->name('professionals.index');

    Route::post(
        '/profissionais',
        [ProfessionalController::class, 'store']
    )->name('professionals.store');


    /*
    |--------------------------------------------------------------------------
    | SERVIÇOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/servicos',
        [ServiceController::class, 'index']
    )->name('services.index');

    Route::post(
        '/servicos',
        [ServiceController::class, 'store']
    )->name('services.store');


    /*
    |--------------------------------------------------------------------------
    | CAMPOS PERSONALIZADOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/campos-personalizados',
        [CustomFieldController::class, 'index']
    )->name('custom-fields.index');

    Route::post(
        '/campos-personalizados',
        [CustomFieldController::class, 'store']
    )->name('custom-fields.store');

    Route::patch(
        '/campos-personalizados/{field}/status',
        [CustomFieldController::class, 'toggle']
    )->name('custom-fields.status');


    /*
    |--------------------------------------------------------------------------
    | FUTUROS MÓDULOS
    |--------------------------------------------------------------------------
    */

    Route::view(
        '/whatsapp',
        'modules.placeholder',
        [
            'module' => 'WhatsApp',
            'icon' => 'bi-whatsapp',
        ]
    )->name('whatsapp.index');

    Route::view(
        '/automacoes',
        'modules.placeholder',
        [
            'module' => 'Automações',
            'icon' => 'bi-lightning-charge',
        ]
    )->name('automations.index');

    Route::view(
        '/relatorios',
        'modules.placeholder',
        [
            'module' => 'Relatórios',
            'icon' => 'bi-bar-chart',
        ]
    )->name('reports.index');

    Route::view(
        '/configuracoes',
        'modules.placeholder',
        [
            'module' => 'Configurações',
            'icon' => 'bi-sliders',
        ]
    )->name('settings.index');


    /*
    |--------------------------------------------------------------------------
    | USUÁRIOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/usuarios',
        [UserController::class, 'index']
    )->name('users.index');

    Route::post(
        '/usuarios',
        [UserController::class, 'store']
    )->name('users.store');

    Route::patch(
        '/usuarios/{user}/status',
        [UserController::class, 'toggleStatus']
    )->name('users.status');

    Route::patch(
        '/usuarios/{user}/senha',
        [UserController::class, 'updatePassword']
    )->name('users.password');


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    )->name('logout');
});

require __DIR__.'/enfas-production.php';

require __DIR__.'/enfas-whatsapp-v6.php';


require __DIR__.'/enfas-v9.php';



require __DIR__.'/enfas-v92.php';

require __DIR__.'/enfas-v10.php';

require __DIR__.'/enfas-v10-1-automation.php';

