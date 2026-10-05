<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SighPharmaceuticalCareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_patient_medication_pmc_lme_and_apac(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $patient = Patient::create([
            'name' => 'Paciente SIGH',
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-EXTERNO-001',
            'preferred_contact_channel' => 'whatsapp',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('sigh.pharmacy.index', ['patient_id' => $patient->id]))
            ->assertOk()
            ->assertSee('Assistência Farmacêutica')
            ->assertSee('PMC')
            ->assertSee('LME')
            ->assertSee('APAC');

        $this->actingAs($admin)
            ->post(route('sigh.pharmacy.medications.store'), [
                'patient_id' => $patient->id,
                'medication_name' => 'Medicamento de Teste',
                'dosage' => '10 mg',
                'route' => 'oral',
                'frequency' => '1x ao dia',
            ])
            ->assertSessionHasNoErrors();

        $patientMedicationId = DB::table('sigh_patient_medications')
            ->where('patient_id', $patient->id)
            ->value('id');

        $this->assertNotNull($patientMedicationId);

        $this->actingAs($admin)
            ->post(route('sigh.pharmacy.pmc.store'), [
                'patient_id' => $patient->id,
                'patient_medication_id' => $patientMedicationId,
                'quantity_at_home' => 30,
                'daily_consumption' => 1,
                'unit' => 'comprimidos',
                'estimated_end_at' => now()->addDays(30)->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('sigh.pharmacy.lme.store'), [
                'patient_id' => $patient->id,
                'medication_name' => 'Medicamento LME',
                'cid10' => 'Z00.0',
                'status' => 'submitted',
                'protocol_number' => 'PROTO-001',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('sigh.pharmacy.apac.store'), [
                'patient_id' => $patient->id,
                'procedure_name' => 'Procedimento de Teste',
                'procedure_code' => '0300000000',
                'competence' => now()->format('Y-m'),
                'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sigh_patient_medications', [
            'patient_id' => $patient->id,
            'medication_name' => 'Medicamento de Teste',
        ]);

        $this->assertDatabaseHas('sigh_pmc_controls', [
            'patient_id' => $patient->id,
            'unit' => 'comprimidos',
        ]);

        $this->assertDatabaseHas('sigh_lme_requests', [
            'patient_id' => $patient->id,
            'protocol_number' => 'PROTO-001',
        ]);

        $this->assertDatabaseHas('sigh_apac_authorizations', [
            'patient_id' => $patient->id,
            'procedure_code' => '0300000000',
        ]);
    }

    public function test_attendant_can_view_but_cannot_manage_pharmaceutical_care(): void
    {
        $attendant = User::factory()->create([
            'role' => 'attendant',
            'permissions' => null,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $patient = Patient::create([
            'name' => 'Paciente Consulta',
            'phone' => '11999999998',
            'rgea_number' => 'RGEA-EXTERNO-002',
            'preferred_contact_channel' => 'whatsapp',
            'is_active' => true,
        ]);

        $this->actingAs($attendant)
            ->get(route('sigh.pharmacy.index', ['patient_id' => $patient->id]))
            ->assertOk();

        $this->actingAs($attendant)
            ->post(route('sigh.pharmacy.medications.store'), [
                'patient_id' => $patient->id,
                'medication_name' => 'Não deve salvar',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('sigh_patient_medications', [
            'medication_name' => 'Não deve salvar',
        ]);
    }
}
