<?php

namespace Tests\Feature;

use App\Http\Controllers\Enfas\HomeController;
use App\Http\Controllers\Enfas\WhatsAppController;
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
        $this->assertTrue(Route::has('patients.show'));
        $this->assertTrue(Route::has('professionals.availability'));
        $this->assertTrue(Route::has('enfas.whatsapp'));
        $this->assertTrue(Route::has('enfas.whatsapp.thread.send'));

        $dashboard = Route::getRoutes()->getByName('dashboard');
        $whatsapp = Route::getRoutes()->getByName('enfas.whatsapp');

        $this->assertSame(
            HomeController::class.'@index',
            $dashboard->getActionName()
        );

        $this->assertSame(
            WhatsAppController::class.'@index',
            $whatsapp->getActionName()
        );
    }
}
