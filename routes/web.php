<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientJourneyController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaitlistController;
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


Route::middleware('auth')->group(function () {
    Route::get(
        '/minha-conta/nova-senha',
        [AuthController::class, 'showPasswordChange']
    )->name('password.change');

    Route::patch(
        '/minha-conta/nova-senha',
        [AuthController::class, 'updateOwnPassword']
    )->name('password.update');
});


/*
|--------------------------------------------------------------------------
| JORNADA PÚBLICA DO PACIENTE
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get(
        '/jornada/{token}',
        [PatientJourneyController::class, 'show']
    )->name('patient-journey.show');

    Route::post(
        '/jornada/{token}/confirmar',
        [PatientJourneyController::class, 'confirm']
    )->name('patient-journey.confirm');

    Route::post(
        '/jornada/{token}/cancelar',
        [PatientJourneyController::class, 'cancel']
    )->name('patient-journey.cancel');

    Route::post(
        '/jornada/{token}/reagendar',
        [PatientJourneyController::class, 'reschedule']
    )->name('patient-journey.reschedule');

    Route::post(
        '/jornada/{token}/check-in',
        [PatientJourneyController::class, 'checkIn']
    )->name('patient-journey.check-in');

    Route::post(
        '/jornada/{token}/avaliacao',
        [PatientJourneyController::class, 'satisfaction']
    )->name('patient-journey.satisfaction');

    Route::get(
        '/jornada/{token}/calendario.ics',
        [PatientJourneyController::class, 'calendar']
    )->name('patient-journey.calendar');
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


    /*
    |--------------------------------------------------------------------------
    | AGENDA
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/agenda',
        [AgendaController::class, 'index']
    )->middleware('permission:agenda.view')->name('agenda.index');

    Route::get(
        '/agenda/eventos',
        [AgendaController::class, 'events']
    )->middleware('permission:agenda.view')->name('agenda.events');

    Route::get(
        '/agenda/melhores-horarios',
        [AgendaController::class, 'bestSlots']
    )->middleware('permission:agenda.view')->name('agenda.best-slots');

    Route::patch(
        '/agenda/agendamentos/{appointment}/mover',
        [AgendaController::class, 'move']
    )->middleware('permission:agenda.manage')->name('agenda.move');


    /*
    |--------------------------------------------------------------------------
    | AGENDAMENTOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/agendamentos',
        [AppointmentController::class, 'index']
    )->middleware('permission:agenda.view')->name('appointments.index');

    Route::post(
        '/agendamentos',
        [AppointmentController::class, 'store']
    )->middleware('permission:agenda.manage')->name('appointments.store');

    Route::post(
        '/agendamentos/recorrentes',
        [AppointmentController::class, 'storeRecurring']
    )->middleware('permission:agenda.manage')->name('appointments.recurring.store');

    Route::get(
        '/agendamentos/{appointment}',
        [AppointmentController::class, 'show']
    )->middleware('permission:agenda.view')->name('appointments.show');

    Route::patch(
        '/agendamentos/{appointment}/status',
        [AppointmentController::class, 'status']
    )->middleware('permission:agenda.manage')->name('appointments.status');

    Route::post(
        '/agendamentos/{appointment}/contato',
        [AppointmentController::class, 'contact']
    )->middleware('permission:agenda.manage')->name('appointments.contact');


    /*
    |--------------------------------------------------------------------------
    | PACIENTES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pacientes',
        [PatientController::class, 'index']
    )->middleware('permission:patients.view')->name('patients.index');

    Route::post(
        '/pacientes',
        [PatientController::class, 'store']
    )->middleware('permission:patients.manage')->name('patients.store');

    Route::get(
        '/pacientes/{patient}',
        [PatientController::class, 'show']
    )->middleware('permission:patients.view')->name('patients.show');

    Route::patch(
        '/pacientes/{patient}',
        [PatientController::class, 'update']
    )->middleware('permission:patients.manage')->name('patients.update');

    Route::post(
        '/pacientes/{patient}/contato',
        [PatientController::class, 'contact']
    )->middleware('permission:patients.manage')->name('patients.contact');


    /*
    |--------------------------------------------------------------------------
    | PROFISSIONAIS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profissionais',
        [ProfessionalController::class, 'index']
    )->middleware('permission:professionals.view')->name('professionals.index');

    Route::post(
        '/profissionais',
        [ProfessionalController::class, 'store']
    )->middleware('permission:professionals.manage')->name('professionals.store');

    Route::patch(
        '/profissionais/{professional}',
        [ProfessionalController::class, 'update']
    )->middleware('permission:professionals.manage')->name('professionals.update');

    Route::post(
        '/profissionais/{professional}/disponibilidade',
        [ProfessionalController::class, 'saveAvailability']
    )->middleware('permission:professionals.manage')->name('professionals.availability');

    Route::post(
        '/profissionais/{professional}/bloqueios',
        [ProfessionalController::class, 'addBlock']
    )->middleware('permission:professionals.manage')->name('professionals.blocks.store');

    Route::delete(
        '/profissionais/{professional}/bloqueios/{block}',
        [ProfessionalController::class, 'deleteBlock']
    )->middleware('permission:professionals.manage')->name('professionals.blocks.destroy');


    /*
    |--------------------------------------------------------------------------
    | SERVIÇOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/servicos',
        [ServiceController::class, 'index']
    )->middleware('permission:services.view')->name('services.index');

    Route::post(
        '/servicos',
        [ServiceController::class, 'store']
    )->middleware('permission:services.manage')->name('services.store');

    Route::patch(
        '/servicos/{service}',
        [ServiceController::class, 'update']
    )->middleware('permission:services.manage')->name('services.update');

    Route::patch(
        '/servicos/{service}/status',
        [ServiceController::class, 'toggle']
    )->middleware('permission:services.manage')->name('services.status');


    /*
    |--------------------------------------------------------------------------
    | CAMPOS PERSONALIZADOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/campos-personalizados',
        [CustomFieldController::class, 'index']
    )->middleware('permission:settings.manage')->name('custom-fields.index');

    Route::post(
        '/campos-personalizados',
        [CustomFieldController::class, 'store']
    )->middleware('permission:settings.manage')->name('custom-fields.store');

    Route::patch(
        '/campos-personalizados/{field}/status',
        [CustomFieldController::class, 'toggle']
    )->middleware('permission:settings.manage')->name('custom-fields.status');




    /*
    |--------------------------------------------------------------------------
    | LISTA DE ESPERA
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/lista-de-espera',
        [WaitlistController::class, 'index']
    )->middleware('permission:agenda.view')->name('waitlist.index');

    Route::post(
        '/lista-de-espera',
        [WaitlistController::class, 'store']
    )->middleware('permission:agenda.manage')->name('waitlist.store');

    Route::patch(
        '/lista-de-espera/{entry}/cancelar',
        [WaitlistController::class, 'cancel']
    )->middleware('permission:agenda.manage')->name('waitlist.cancel');

    /*
    |--------------------------------------------------------------------------
    | USUÁRIOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/usuarios',
        [UserController::class, 'index']
    )->middleware('permission:users.manage')->name('users.index');

    Route::post(
        '/usuarios',
        [UserController::class, 'store']
    )->middleware('permission:users.manage')->name('users.store');

    Route::patch(
        '/usuarios/{user}',
        [UserController::class, 'update']
    )->middleware('permission:users.manage')->name('users.update');

    Route::patch(
        '/usuarios/{user}/status',
        [UserController::class, 'toggleStatus']
    )->middleware('permission:users.manage')->name('users.status');

    Route::patch(
        '/usuarios/{user}/senha',
        [UserController::class, 'updatePassword']
    )->middleware('permission:users.manage')->name('users.password');


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

