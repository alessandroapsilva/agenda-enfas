<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClinicalProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped(
            'Modulo clinico legado fora do escopo da Agenda ENFAS.'
        );
    }

    use RefreshDatabase;

    public function test_clinical_profile_is_longitudinal_and_audited(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        [$appointmentId, $professionalId, $patientId] = $this->makeAppointment();

        $this->actingAs($admin)
            ->put(route('clinical-profile.history.update', $appointmentId), [
                'chronic_conditions' => 'Condição crônica registrada',
                'surgeries' => 'Procedimento prévio',
                'family_history' => 'Histórico familiar relevante',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('clinical-profile.allergies.store', $appointmentId), [
                'substance' => 'Substância de teste',
                'reaction' => 'Reação descrita',
                'severity' => 'moderate',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('clinical-profile.problems.store', $appointmentId), [
                'description' => 'Problema clínico de teste',
                'code_system' => 'LOCAL',
                'code' => 'P001',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('clinical-profile.medications.store', $appointmentId), [
                'medication_name' => 'Medicamento em uso',
                'concentration' => '10 mg',
                'route' => 'oral',
                'directions' => 'Conforme orientação.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('clinical-profile.scales.store', $appointmentId), [
                'scale_name' => 'Escala de teste',
                'scale_key' => 'TEST',
                'score' => 7,
                'classification' => 'Registrado',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('clinical-profile.care-plans.store', $appointmentId), [
                'goal' => 'Meta clínica de teste',
                'actions' => 'Ações planejadas.',
                'responsible_professional_id' => $professionalId,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('patient_clinical_histories', [
            'patient_id' => $patientId,
            'chronic_conditions' => 'Condição crônica registrada',
        ]);

        $this->assertDatabaseHas('patient_allergies', [
            'patient_id' => $patientId,
            'substance' => 'Substância de teste',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('patient_problems', [
            'patient_id' => $patientId,
            'description' => 'Problema clínico de teste',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('patient_medications', [
            'patient_id' => $patientId,
            'medication_name' => 'Medicamento em uso',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('appointment_clinical_scales', [
            'appointment_id' => $appointmentId,
            'scale_name' => 'Escala de teste',
        ]);

        $this->assertDatabaseHas('clinical_care_plans', [
            'appointment_id' => $appointmentId,
            'goal' => 'Meta clínica de teste',
            'status' => 'planned',
        ]);

        $this->assertGreaterThanOrEqual(
            6,
            DB::table('patient_clinical_events')
                ->where('patient_id', $patientId)
                ->count()
        );

        $this->actingAs($admin)
            ->get(route('appointments.record', $appointmentId))
            ->assertOk()
            ->assertSee('Substância de teste')
            ->assertSee('Problema clínico de teste')
            ->assertSee('Medicamento em uso')
            ->assertSee('Escala de teste')
            ->assertSee('Meta clínica de teste');
    }

    public function test_enhanced_record_fields_are_locked_after_finalization(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        [$appointmentId] = $this->makeAppointment('Bloqueio');

        $this->actingAs($admin)
            ->put(route('appointments.record.save', $appointmentId), [
                'reason_for_visit' => 'Atendimento clínico',
                'history' => 'História registrada',
                'physical_exam' => 'Exame físico registrado',
                'assessment' => 'Avaliação registrada',
                'clinical_impression' => 'Impressão clínica registrada',
                'care_plan_summary' => 'Plano assistencial resumido',
                'vitals' => [
                    'pain_score' => 4,
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('appointments.record.finalize', $appointmentId))
            ->assertSessionHasNoErrors();

        $record = DB::table('appointment_clinical_records')
            ->where('appointment_id', $appointmentId)
            ->first();

        $this->assertSame('finalized', $record->status);
        $this->assertSame('Exame físico registrado', $record->physical_exam);
        $this->assertSame('Impressão clínica registrada', $record->clinical_impression);
        $this->assertSame(64, strlen((string) $record->integrity_hash));

        $this->actingAs($admin)
            ->put(route('appointments.record.save', $appointmentId), [
                'physical_exam' => 'Tentativa de alteração',
            ])
            ->assertSessionHasErrors('record');
    }

    public function test_professional_cannot_write_longitudinal_data_from_another_professionals_appointment(): void
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
            ->post(route('clinical-profile.allergies.store', $ownAppointmentId), [
                'substance' => 'Alergia própria',
                'severity' => 'unknown',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($professional)
            ->post(route('clinical-profile.allergies.store', $otherAppointmentId), [
                'substance' => 'Não permitido',
                'severity' => 'unknown',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('patient_allergies', [
            'substance' => 'Não permitido',
        ]);
    }

    private function makeAppointment(string $suffix = 'Perfil'): array
    {
        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente '.$suffix,
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-'.strtoupper(substr(md5($suffix), 0, 8)),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional '.$suffix,
            'council_type' => 'COREN',
            'council_number' => (string) random_int(100000, 999999),
            'council_state' => 'SP',
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
            'public_token' => (string) Str::uuid(),
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
