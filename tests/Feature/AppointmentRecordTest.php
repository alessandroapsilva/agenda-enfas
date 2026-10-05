<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppointmentRecordTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(): Appointment
    {
        static $seq = 0;
        $seq++;

        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Prontuário',
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-PEP-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'preferred_contact_channel' => 'whatsapp',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Prontuário '.$seq,
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
            'name' => 'Consulta de Enfermagem',
            'duration_minutes' => 30,
            'arrival_minutes' => 0,
            'color' => '#2563eb',
            'is_active' => true,
            'allow_online_reschedule' => true,
            'allow_recurrence' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Appointment::create([
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'start_at' => now()->addHour(),
            'end_at' => now()->addMinutes(90),
            'duration_minutes' => 30,
            'status' => 'confirmed',
            'confirmation_status' => 'confirmed',
            'source' => 'internal',
            'appointment_type' => 'care',
        ]);
    }

    public function test_admin_can_save_finalize_and_add_addendum(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $appointment = $this->makeAppointment();

        $this->actingAs($admin)
            ->get(route('appointments.record', $appointment))
            ->assertOk()
            ->assertSee('Prontuário do atendimento');

        $this->actingAs($admin)
            ->put(route('appointments.record.save', $appointment), [
                'reason_for_visit' => 'Avaliação de rotina',
                'evolution' => 'Paciente em bom estado geral.',
                'vitals' => [
                    'systolic_bp' => 120,
                    'diastolic_bp' => 80,
                    'spo2' => 98,
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointment_clinical_records', [
            'appointment_id' => $appointment->id,
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->post(route('appointments.record.finalize', $appointment))
            ->assertSessionHasNoErrors();

        $record = DB::table('appointment_clinical_records')
            ->where('appointment_id', $appointment->id)
            ->first();

        $this->assertSame('finalized', $record->status);
        $this->assertNotNull($record->integrity_hash);

        $this->actingAs($admin)
            ->put(route('appointments.record.save', $appointment), [
                'evolution' => 'Tentativa de alteração posterior',
            ])
            ->assertSessionHasErrors('record');

        $this->actingAs($admin)
            ->post(route('appointments.record.addendum', $appointment), [
                'body' => 'Complementação posterior sem alterar o registro original.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointment_clinical_addenda', [
            'clinical_record_id' => $record->id,
        ]);
    }

    public function test_professional_only_reads_own_appointment_record(): void
    {
        $appointment = $this->makeAppointment();

        $professional = User::factory()->create([
            'role' => 'professional',
            'professional_id' => $appointment->professional_id,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($professional)
            ->get(route('appointments.record', $appointment))
            ->assertOk();

        $other = $this->makeAppointment();

        $this->actingAs($professional)
            ->get(route('appointments.record', $other))
            ->assertForbidden();
    }
}
