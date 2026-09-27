<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PremiumUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_primary_unit_and_use_it_on_appointment(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.unidade',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('v9.locations.store'), [
                'name' => 'Unidade Central',
                'code' => 'central',
                'phone' => '1133334444',
                'whatsapp' => '11999999999',
                'email' => 'central@example.test',
                'address' => 'Rua Exemplo',
                'address_number' => '100',
                'address_complement' => 'Sala 1',
                'neighborhood' => 'Centro',
                'city' => 'Vargem Grande Paulista',
                'state' => 'SP',
                'postal_code' => '06730-000',
                'responsible_name' => 'Responsável Teste',
                'opening_hours' => 'Segunda a sexta, 08h às 18h',
                'patient_instructions' => 'Apresente documento com foto.',
                'is_main' => 1,
            ])
            ->assertRedirect('/locais');

        $location = DB::table('locations')
            ->where('code', 'CENTRAL')
            ->first();

        $this->assertNotNull($location);
        $this->assertTrue((bool) $location->is_main);

        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Unidade',
            'phone' => '11999999998',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Unidade',
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
            'name' => 'Atendimento Unidade',
            'duration_minutes' => 30,
            'color' => '#2563eb',
            'is_active' => true,
            'allow_online_reschedule' => true,
            'allow_recurrence' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('appointments.store'), [
                'patient_id' => $patientId,
                'professional_id' => $professionalId,
                'service_id' => $serviceId,
                'location_id' => $location->id,
                'start_at' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s'),
                'appointment_type' => 'care',
            ])
            ->assertRedirect(route('agenda.index'));

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patientId,
            'location_id' => $location->id,
        ]);
    }

    public function test_unit_code_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin.unidade.unique',
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        DB::table('locations')->insert([
            'name' => 'Unidade Um',
            'code' => 'U001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('v9.locations.store'), [
                'name' => 'Unidade Dois',
                'code' => 'U001',
                'is_main' => 0,
            ])
            ->assertSessionHasErrors('code');
    }
}
