<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicalDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped(
            'Modulo clinico legado fora do escopo da Agenda ENFAS.'
        );
    }

    use RefreshDatabase;

    private function makeContext(): array
    {
        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Documento',
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-DOC-001',
            'preferred_contact_channel' => 'whatsapp',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table('professionals')->insertGetId([
            'name' => 'Profissional Documento',
            'specialty' => 'Enfermagem',
            'council_type' => 'COREN',
            'council_number' => '123456',
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
            'name' => 'Consulta',
            'duration_minutes' => 30,
            'arrival_minutes' => 0,
            'color' => '#2563eb',
            'is_active' => true,
            'allow_online_reschedule' => true,
            'allow_recurrence' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment = Appointment::create([
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

        return [$patientId, $professionalId, $appointment];
    }

    public function test_admin_can_create_sign_and_print_document(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        [$patientId, $professionalId, $appointment] = $this->makeContext();

        $this->actingAs($admin)
            ->post(route('clinical-documents.store'), [
                'patient_id' => $patientId,
                'appointment_id' => $appointment->id,
                'professional_id' => $professionalId,
                'document_type' => 'report',
                'title' => 'Relatório de atendimento',
                'content' => 'Conteúdo clínico revisado.',
            ])
            ->assertRedirect();

        $document = ClinicalDocument::firstOrFail();

        $this->assertSame('draft', $document->status);

        $this->actingAs($admin)
            ->post(route('clinical-documents.sign', $document))
            ->assertSessionHasNoErrors();

        $document->refresh();

        $this->assertSame('signed', $document->status);
        $this->assertNotNull($document->content_hash);
        $this->assertDatabaseHas('clinical_document_signatures', [
            'clinical_document_id' => $document->id,
            'signature_type' => 'electronic',
            'provider' => 'agenda_enfas',
        ]);

        $this->actingAs($admin)
            ->patch(route('clinical-documents.update', $document), [
                'title' => 'Alterado',
                'content' => 'Não deve alterar.',
            ])
            ->assertSessionHasErrors('document');

        $this->actingAs($admin)
            ->get(route('clinical-documents.print', $document))
            ->assertOk()
            ->assertSee('Conteúdo clínico revisado.');
    }

    public function test_professional_can_only_access_document_for_own_patient_context(): void
    {
        [$patientId, $professionalId, $appointment] = $this->makeContext();

        $document = ClinicalDocument::create([
            'patient_id' => $patientId,
            'appointment_id' => $appointment->id,
            'professional_id' => $professionalId,
            'document_type' => 'report',
            'title' => 'Relatório',
            'content' => 'Conteúdo',
            'status' => 'draft',
            'version' => 1,
        ]);

        $professional = User::factory()->create([
            'role' => 'professional',
            'professional_id' => $professionalId,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($professional)
            ->get(route('clinical-documents.show', $document))
            ->assertOk();

        DB::table('appointments')
            ->where('id', $appointment->id)
            ->update(['professional_id' => DB::table('professionals')->insertGetId([
                'name' => 'Outro Profissional',
                'work_start' => '08:00:00',
                'work_end' => '18:00:00',
                'active_days' => json_encode([1,2,3,4,5]),
                'slot_interval' => 30,
                'color' => '#2563eb',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])]);

        $this->actingAs($professional)
            ->get(route('clinical-documents.show', $document))
            ->assertForbidden();
    }
}
