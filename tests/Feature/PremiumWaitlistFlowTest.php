<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\WaMessage;
use App\Models\WaitlistEntry;
use App\Services\Enfas\AvailabilityService;
use App\Services\Enfas\MetaWhatsAppService;
use App\Services\Enfas\WaitlistService;
use App\Services\Enfas\WhatsAppDispatchPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PremiumWaitlistFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_freed_slot_is_offered_and_acceptance_creates_appointment_in_same_unit(): void
    {
        Carbon::setTestNow('2026-09-28 08:00:00');

        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Espera',
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-WAIT-001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Espera',
            'work_start' => '08:00:00',
            'work_end' => '18:00:00',
            'active_days' => json_encode([0,1,2,3,4,5,6]),
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table('services')->insertGetId([
            'name' => 'Serviço Espera',
            'duration_minutes' => 30,
            'arrival_minutes' => 0,
            'color' => '#2563eb',
            'is_active' => true,
            'allow_online_reschedule' => true,
            'allow_recurrence' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $locationId = DB::table('locations')->insertGetId([
            'name' => 'Unidade Espera',
            'code' => 'WAIT',
            'is_main' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entry = WaitlistEntry::create([
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'professional_id' => null,
            'location_id' => $locationId,
            'preferred_period' => 'morning',
            'earliest_date' => '2026-09-28',
            'latest_date' => '2026-10-10',
            'status' => 'waiting',
        ]);

        $cancelled = Appointment::create([
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'location_id' => $locationId,
            'start_at' => '2026-09-29 10:00:00',
            'end_at' => '2026-09-29 10:30:00',
            'duration_minutes' => 30,
            'status' => 'cancelled',
            'confirmation_status' => 'cancelled',
            'source' => 'internal',
        ]);

        $meta = Mockery::mock(MetaWhatsAppService::class);
        $meta->shouldReceive('sendInteractiveButtons')
            ->once()
            ->andReturn(new WaMessage());

        $waitlist = new WaitlistService(
            app(AvailabilityService::class),
            $meta,
            app(WhatsAppDispatchPolicy::class)
        );

        $offered = $waitlist->offerFreedSlot($cancelled);

        $this->assertNotNull($offered);

        $entry->refresh();

        $this->assertSame('offered', $entry->status);
        $this->assertSame($professionalId, $entry->professional_id);
        $this->assertSame($locationId, $entry->location_id);

        $newAppointment = $waitlist->accept($entry->id);

        $this->assertSame('confirmed', $newAppointment->status);
        $this->assertSame($locationId, $newAppointment->location_id);
        $this->assertSame('waitlist', $newAppointment->source);

        $entry->refresh();

        $this->assertSame('accepted', $entry->status);
        $this->assertSame($newAppointment->id, $entry->appointment_id);
    }
}
