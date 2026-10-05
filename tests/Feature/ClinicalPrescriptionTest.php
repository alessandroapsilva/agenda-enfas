<?php

namespace Tests\Feature;

use App\Models\ClinicalPrescription;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalPrescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prescription_is_structured_signed_and_locked(): void
    {
        $professional = Professional::create([
            'name' => 'Profissional Prescritor',
            'specialty' => 'Enfermagem',
            'council_type' => 'COREN',
            'council_number' => '123456',
            'council_state' => 'SP',
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'name' => 'Paciente Prescrição',
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-RX-001',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Profissional Prescritor',
            'role' => 'admin',
            'professional_id' => $professional->id,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($user)
            ->post(route('clinical-prescriptions.store'), [
                'patient_id' => $patient->id,
                'professional_id' => $professional->id,
                'title' => 'Prescrição de teste',
                'notes' => 'Orientação geral.',
            ])
            ->assertRedirect();

        $prescription = ClinicalPrescription::first();

        $this->assertNotNull($prescription);
        $this->assertSame('draft', $prescription->status);

        $this->actingAs($user)
            ->post(route('clinical-prescriptions.items.store', $prescription), [
                'medication_name' => 'Medicamento de teste',
                'concentration' => '10 mg',
                'dosage_form' => 'comprimido',
                'route' => 'oral',
                'quantity' => '1 caixa',
                'directions' => 'Usar conforme orientação do profissional.',
                'duration' => '5 dias',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('clinical-prescriptions.sign', $prescription))
            ->assertSessionHasNoErrors();

        $prescription->refresh();

        $this->assertSame('signed', $prescription->status);
        $this->assertNotNull($prescription->clinical_document_id);
        $this->assertSame('agenda_enfas', $prescription->external_provider);
        $this->assertSame(64, strlen((string) $prescription->content_hash));

        $this->assertDatabaseHas('clinical_documents', [
            'id' => $prescription->clinical_document_id,
            'document_type' => 'prescription',
            'status' => 'signed',
            'external_provider' => 'agenda_enfas',
        ]);

        $this->assertDatabaseHas('clinical_document_signatures', [
            'clinical_document_id' => $prescription->clinical_document_id,
            'provider' => 'agenda_enfas',
        ]);

        $this->actingAs($user)
            ->post(route('clinical-prescriptions.items.store', $prescription), [
                'medication_name' => 'Tentativa de alteração',
                'directions' => 'Não deve ser incluído.',
            ])
            ->assertSessionHasErrors('prescription');

        $this->assertDatabaseMissing('clinical_prescription_items', [
            'clinical_prescription_id' => $prescription->id,
            'medication_name' => 'Tentativa de alteração',
        ]);
    }

    public function test_only_responsible_professional_can_sign_prescription(): void
    {
        $responsible = Professional::create([
            'name' => 'Responsável',
            'council_type' => 'COREN',
            'council_number' => '111111',
            'council_state' => 'SP',
            'is_active' => true,
        ]);

        $other = Professional::create([
            'name' => 'Outro profissional',
            'council_type' => 'COREN',
            'council_number' => '222222',
            'council_state' => 'SP',
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'name' => 'Paciente',
            'phone' => '11988888888',
            'is_active' => true,
        ]);

        $creator = User::factory()->create([
            'role' => 'admin',
            'professional_id' => $responsible->id,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $otherUser = User::factory()->create([
            'role' => 'admin',
            'professional_id' => $other->id,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($creator)->post(route('clinical-prescriptions.store'), [
            'patient_id' => $patient->id,
            'professional_id' => $responsible->id,
        ]);

        $prescription = ClinicalPrescription::first();

        $this->actingAs($creator)->post(route('clinical-prescriptions.items.store', $prescription), [
            'medication_name' => 'Medicamento',
            'directions' => 'Conforme orientação.',
        ]);

        $this->actingAs($otherUser)
            ->post(route('clinical-prescriptions.sign', $prescription))
            ->assertSessionHasErrors('prescription');

        $this->assertDatabaseHas('clinical_prescriptions', [
            'id' => $prescription->id,
            'status' => 'draft',
        ]);
    }
}
