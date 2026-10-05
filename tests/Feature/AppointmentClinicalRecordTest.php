<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppointmentClinicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_finalize_and_append_to_appointment_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        [$appointmentId] = $this->makeAppointment();

        $this->actingAs($admin)
            ->get(route('appointments.record', $appointmentId))
            ->assertOk()
            ->assertSee('Prontuário do atendimento');

        $this->actingAs($admin)
            ->put(route('appointments.record.save', $appointmentId), [
                'reason_for_visit' => 'Consulta programada',
                'history' => 'Relato do paciente',
                'vitals' => [
                    'systolic_bp' => 120,
                    'diastolic_bp' => 80,
                    'heart_rate' => 76,
                    'spo2' => 98,
                    'temperature' => 36.5,
                ],
                'assessment' => 'Avaliação registrada',
                'evolution' => 'Evolução registrada',
                'follow_up_plan' => 'Retorno conforme orientação',
            ])
            ->assertSessionHasNoErrors();

        $record = DB::table('appointment_clinical_records')
            ->where('appointment_id', $appointmentId)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('draft', $record->status);

        $this->actingAs($admin)
            ->post(route('appointments.record.finalize', $appointmentId))
            ->assertSessionHasNoErrors();

        $record = DB::table('appointment_clinical_records')
            ->where('appointment_id', $appointmentId)
            ->first();

        $this->assertSame('finalized', $record->status);
        $this->assertNotNull($record->finalized_at);
        $this->assertSame(64, strlen((string) $record->integrity_hash));

        $this->actingAs($admin)
            ->put(route('appointments.record.save', $appointmentId), [
                'evolution' => 'Tentativa de alterar original',
            ])
            ->assertSessionHasErrors('record');

        $this->actingAs($admin)
            ->post(route('appointments.record.addendum', $appointmentId), [
                'body' => 'Complementação posterior devidamente registrada.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointment_clinical_addenda', [
            'clinical_record_id' => $record->id,
            'body' => 'Complementação posterior devidamente registrada.',
        ]);
    }

    public function test_professional_only_accesses_record_from_own_appointment(): void
    {
        [$ownAppointmentId, $ownProfessionalId] = $this->makeAppointment('Próprio');
        [$otherAppointmentId] = $this->makeAppointment('Outro');

        $professional = User::factory()->create([
            'role' => 'professional',
            'professional_id' => $ownProfessionalId,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($professional)
            ->get(route('appointments.record', $ownAppointmentId))
            ->assertOk();

        $this->actingAs($professional)
            ->get(route('appointments.record', $otherAppointmentId))
            ->assertForbidden();
    }

    private function makeAppointment(string $suffix = 'Teste'): array
    {
        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente '.$suffix,
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-'.strtoupper($suffix).'-'.random_int(1000, 9999),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional '.$suffix,
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
            'name' => 'Consulta '.$suffix,
            'duration_minutes' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'allow_online_reschedule' => true,
            'allow_recurrence' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'code' => 'AG-'.strtoupper(substr(md5($suffix.microtime(true)), 0, 6)),
            'public_token' => (string) \Illuminate\Support\Str::uuid(),
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'start_at' => now(),
            'end_at' => now()->addMinutes(30),
            'duration_minutes' => 30,
            'status' => 'confirmed',
            'confirmation_status' => 'confirmed',
            'source' => 'internal',
            'appointment_type' => 'care',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$appointmentId, $professionalId, $patientId];
    }
}
