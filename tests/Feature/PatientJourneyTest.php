<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PatientJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function seedAppointment(): array
    {
        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Teste',
            'phone' => '11999999999',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Teste',
            'work_start' => '08:00:00',
            'work_end' => '18:00:00',
            'active_days' => json_encode([1,2,3,4,5]),
            'slot_interval' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table('services')->insertGetId([
            'name' => 'Consulta Teste',
            'duration_minutes' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'arrival_minutes' => 15,
            'required_documents' => 'Documento com foto',
            'preparation_instructions' => 'Chegar hidratado.',
            'aftercare_instructions' => 'Manter repouso leve.',
            'allow_online_reschedule' => true,
            'allow_recurrence' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = (string) Str::uuid();

        $appointmentId = DB::table('appointments')->insertGetId([
            'code' => 'AG-TESTE1',
            'public_token' => $token,
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'start_at' => '2026-09-28 09:00:00',
            'end_at' => '2026-09-28 09:30:00',
            'duration_minutes' => 30,
            'status' => 'awaiting_confirmation',
            'confirmation_status' => 'pending',
            'source' => 'internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$appointmentId, $token];
    }

    public function test_patient_can_use_public_journey(): void
    {
        Carbon::setTestNow('2026-09-28 08:00:00');

        [$appointmentId, $token] = $this->seedAppointment();

        $this->get(route('patient-journey.show', $token))
            ->assertOk()
            ->assertSee('Paciente Teste')
            ->assertSee('Consulta Teste')
            ->assertSee('Confirmar presença');

        $this->post(route('patient-journey.confirm', $token))
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => 'confirmed',
            'confirmation_status' => 'confirmed',
            'confirmation_channel' => 'patient_journey',
        ]);

        $this->post(route('patient-journey.check-in', $token))
            ->assertRedirect();

        $this->assertNotNull(
            DB::table('appointments')->where('id', $appointmentId)->value('check_in_completed_at')
        );

        $this->post(route('patient-journey.reschedule', $token), [
            'start_at' => '2026-09-28 10:30:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'start_at' => '2026-09-28 10:30:00',
            'status' => 'awaiting_confirmation',
            'confirmation_status' => 'pending',
        ]);

        $this->assertDatabaseHas('slot_reservations', [
            'appointment_id' => $appointmentId,
            'status' => 'consumed',
        ]);

        DB::table('appointments')->where('id', $appointmentId)->update([
            'status' => 'completed',
        ]);

        $this->post(route('patient-journey.satisfaction', $token), [
            'score' => 10,
            'stars' => 5,
            'comment' => 'Ótimo atendimento.',
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'satisfaction_score' => 10,
            'satisfaction_stars' => 5,
            'satisfaction_comment' => 'Ótimo atendimento.',
        ]);

        $this->get(route('patient-journey.calendar', $token))
            ->assertOk()
            ->assertHeader('content-type', 'text/calendar; charset=utf-8');
    }
}
