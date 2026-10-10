<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PremiumAppointmentSlotEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_time_outside_configured_slot_grid_is_blocked(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2026-10-10 10:00:00',
                config('app.timezone')
            )
        );

        $admin = User::factory()->create([
            'username' => 'admin.slot.enforcement',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $patientId = DB::table(
            'patients'
        )->insertGetId([
            'name' => 'Paciente Teste',
            'phone' => '11999999999',
            'email' => 'paciente@example.com',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table(
            'professionals'
        )->insertGetId([
            'name' => 'Profissional Teste',
            'work_start' => '08:00:00',
            'work_end' => '12:00:00',
            'active_days' => json_encode([1]),
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'whatsapp_notifications_enabled' => false,
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table(
            'services'
        )->insertGetId([
            'name' => 'Consulta Teste',
            'duration_minutes' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table(
            'professional_service'
        )->insert([
            'professional_id' =>
                $professionalId,
            'service_id' =>
                $serviceId,
        ]);

        DB::table(
            'professional_availabilities'
        )->insert([
            'professional_id' =>
                $professionalId,
            'weekday' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '12:00:00',
            'slot_minutes' => 30,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'break_start' => null,
            'break_end' => null,
            'location_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('agenda.index'))
            ->post(
                route('appointments.store'),
                [
                    'patient_id' =>
                        $patientId,
                    'professional_id' =>
                        $professionalId,
                    'service_id' =>
                        $serviceId,
                    'start_at' =>
                        '2026-10-12 08:10:00',
                    'appointment_type' =>
                        'care',
                ]
            )
            ->assertRedirect(
                route('agenda.index')
            )
            ->assertSessionHasErrors(
                'start_at'
            );

        $this->assertDatabaseCount(
            'appointments',
            0
        );

        Carbon::setTestNow();
    }
}
