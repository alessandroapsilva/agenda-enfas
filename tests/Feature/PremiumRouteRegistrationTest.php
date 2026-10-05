<?php

namespace Tests\Feature;

use App\Http\Controllers\Enfas\HomeController;
use App\Http\Controllers\Enfas\WhatsAppController;
use App\Http\Controllers\Enfas\V9\TemplateStudioController;
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
        $this->assertTrue(Route::has('enfas.whatsapp.thread.context'));
        $this->assertTrue(Route::has('enfas.whatsapp.thread.tasks.store'));
        $this->assertTrue(Route::has('enfas.whatsapp.tasks.complete'));
        $this->assertTrue(Route::has('enfas.whatsapp.quick-replies.store'));
        $this->assertTrue(Route::has('activities.index'));
        $this->assertTrue(Route::has('activities.store'));
        $this->assertTrue(Route::has('activities.complete'));
        $this->assertTrue(Route::has('appointments.record'));
        $this->assertTrue(Route::has('appointments.record.save'));
        $this->assertTrue(Route::has('appointments.record.finalize'));
        $this->assertTrue(Route::has('appointments.record.addendum'));
        $this->assertTrue(Route::has('clinical-documents.index'));
        $this->assertTrue(Route::has('clinical-documents.store'));
        $this->assertTrue(Route::has('clinical-documents.show'));
        $this->assertTrue(Route::has('clinical-documents.update'));
        $this->assertTrue(Route::has('clinical-documents.sign'));
        $this->assertTrue(Route::has('clinical-documents.print'));

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

    public function test_whatsapp_template_routes_use_single_consolidated_controller(): void
    {
        $index = Route::getRoutes()->getByName('v9.templates.index');
        $store = Route::getRoutes()->getByName('v9.templates.store');
        $sync = Route::getRoutes()->getByName('v9.templates.sync');

        $this->assertSame(
            TemplateStudioController::class.'@index',
            $index->getActionName()
        );

        $this->assertSame(
            TemplateStudioController::class.'@store',
            $store->getActionName()
        );

        $this->assertSame(
            TemplateStudioController::class.'@sync',
            $sync->getActionName()
        );

        $this->assertFalse(Route::has('enfas.v6.templates'));
        $this->assertFalse(Route::has('enfas.v6.templates.store'));
        $this->assertFalse(Route::has('enfas.v6.templates.sync'));
    }

}
