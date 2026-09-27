<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MedicationPickupAndRgeaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_unique_rgea_and_schedule_medication_pickup(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.rgea',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Retirada',
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
            'name' => 'Retirada de medicamento',
            'duration_minutes' => 15,
            'arrival_minutes' => 0,
            'color' => '#2563eb',
            'is_active' => true,
            'allow_online_reschedule' => true,
            'allow_recurrence' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('patients.store'), [
                'name' => 'Paciente RGEA',
                'phone' => '11999999999',
                'email' => 'rgea@example.test',
                'preferred_contact_channel' => 'whatsapp',
                'contact_consent' => 1,
                'do_not_contact' => 0,
                'rgea_number' => 'RGEA-000123',
            ])
            ->assertRedirect();

        $patientId = DB::table('patients')
            ->where('rgea_number', 'RGEA-000123')
            ->value('id');

        $this->assertNotNull($patientId);

        $this->actingAs($admin)
            ->post(route('appointments.store'), [
                'patient_id' => $patientId,
                'professional_id' => $professionalId,
                'service_id' => $serviceId,
                'start_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'appointment_type' => 'medication_pickup',
                'medication_name' => 'Medicamento Teste',
                'medication_quantity' => '2 caixas',
                'medication_notes' => 'Retirada administrativa.',
            ])
            ->assertRedirect(route('agenda.index'));

        $appointmentId = DB::table('appointments')
            ->where('patient_id', $patientId)
            ->where('appointment_type', 'medication_pickup')
            ->value('id');

        $this->assertNotNull($appointmentId);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'medication_name' => 'Medicamento Teste',
            'medication_quantity' => '2 caixas',
            'pickup_status' => 'scheduled',
        ]);

        $this->actingAs($admin)
            ->patch(route('medication-pickups.status', $appointmentId), [
                'pickup_status' => 'collected',
                'pickup_collected_by' => 'Paciente RGEA',
                'pickup_collector_document' => 'DOC-123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'pickup_status' => 'collected',
            'pickup_collected_by' => 'Paciente RGEA',
            'status' => 'completed',
        ]);
    }

    public function test_rgea_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.rgea.unique',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        DB::table('patients')->insert([
            'name' => 'Paciente Um',
            'phone' => '11999999991',
            'rgea_number' => 'RGEA-999',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('patients.store'), [
                'name' => 'Paciente Dois',
                'phone' => '11999999992',
                'preferred_contact_channel' => 'whatsapp',
                'contact_consent' => 1,
                'do_not_contact' => 0,
                'rgea_number' => 'RGEA-999',
            ])
            ->assertSessionHasErrors('rgea_number');
    }
}
