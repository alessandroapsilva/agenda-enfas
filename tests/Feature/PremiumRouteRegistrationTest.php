<?php

namespace Tests\Feature;

use App\Http\Controllers\Enfas\HomeController;
use App\Http\Controllers\Enfas\WhatsAppController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PremiumRouteRegistrationTest extends TestCase
{
    public function test_premium_routes_are_registered_without_legacy_override(): void
    {
        $this->assertTrue(Route::has('dashboard'));
        $this->assertTrue(Route::has('agenda.index'));
        $this->assertTrue(Route::has('agenda.best-slots'));
        $this->assertTrue(Route::has('appointments.recurring.store'));
        $this->assertTrue(Route::has('patients.index'));
        $this->assertTrue(Route::has('patients.show'));
        $this->assertTrue(Route::has('professionals.index'));
        $this->assertTrue(Route::has('professionals.availability'));
        $this->assertTrue(Route::has('services.index'));
        $this->assertTrue(Route::has('waitlist.index'));
        $this->assertTrue(Route::has('professional.workspace'));
        $this->assertTrue(Route::has('v9.locations.index'));
        $this->assertTrue(Route::has('enfas.whatsapp'));
        $this->assertTrue(Route::has('enfas.whatsapp.thread.send'));

        $dashboard = Route::getRoutes()->getByName('dashboard');
        $whatsapp = Route::getRoutes()->getByName('enfas.whatsapp');
        $patients = Route::getRoutes()->getByName('patients.index');
        $professionals = Route::getRoutes()->getByName('professionals.index');
        $services = Route::getRoutes()->getByName('services.index');

        $this->assertSame(
            HomeController::class.'@index',
            $dashboard->getActionName()
        );

        $this->assertSame(
            WhatsAppController::class.'@index',
            $whatsapp->getActionName()
        );

        $this->assertSame(
            PatientController::class.'@index',
            $patients->getActionName()
        );

        $this->assertSame(
            ProfessionalController::class.'@index',
            $professionals->getActionName()
        );

        $this->assertSame(
            ServiceController::class.'@index',
            $services->getActionName()
        );
    }
}
