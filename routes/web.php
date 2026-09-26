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
    )->name('agenda.index');

    Route::get(
        '/agenda/eventos',
        [AgendaController::class, 'events']
    )->name('agenda.events');

    Route::get(
        '/agenda/melhores-horarios',
        [AgendaController::class, 'bestSlots']
    )->name('agenda.best-slots');

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

    Route::post(
        '/agendamentos/recorrentes',
        [AppointmentController::class, 'storeRecurring']
    )->name('appointments.recurring.store');

    Route::get(
        '/agendamentos/{appointment}',
        [AppointmentController::class, 'show']
    )->name('appointments.show');

    Route::patch(
        '/agendamentos/{appointment}/status',
        [AppointmentController::class, 'status']
    )->name('appointments.status');

    Route::post(
        '/agendamentos/{appointment}/contato',
        [AppointmentController::class, 'contact']
    )->name('appointments.contact');


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

    Route::get(
        '/pacientes/{patient}',
        [PatientController::class, 'show']
    )->name('patients.show');

    Route::patch(
        '/pacientes/{patient}',
        [PatientController::class, 'update']
    )->name('patients.update');

    Route::post(
        '/pacientes/{patient}/contato',
        [PatientController::class, 'contact']
    )->name('patients.contact');


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

    Route::patch(
        '/profissionais/{professional}',
        [ProfessionalController::class, 'update']
    )->name('professionals.update');

    Route::post(
        '/profissionais/{professional}/disponibilidade',
        [ProfessionalController::class, 'saveAvailability']
    )->name('professionals.availability');

    Route::post(
        '/profissionais/{professional}/bloqueios',
        [ProfessionalController::class, 'addBlock']
    )->name('professionals.blocks.store');

    Route::delete(
        '/profissionais/{professional}/bloqueios/{block}',
        [ProfessionalController::class, 'deleteBlock']
    )->name('professionals.blocks.destroy');


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

    Route::patch(
        '/servicos/{service}',
        [ServiceController::class, 'update']
    )->name('services.update');

    Route::patch(
        '/servicos/{service}/status',
        [ServiceController::class, 'toggle']
    )->name('services.status');


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
    | LISTA DE ESPERA
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/lista-de-espera',
        [WaitlistController::class, 'index']
    )->name('waitlist.index');

    Route::post(
        '/lista-de-espera',
        [WaitlistController::class, 'store']
    )->name('waitlist.store');

    Route::patch(
        '/lista-de-espera/{entry}/cancelar',
        [WaitlistController::class, 'cancel']
    )->name('waitlist.cancel');

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
        '/usuarios/{user}',
        [UserController::class, 'update']
    )->name('users.update');

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

