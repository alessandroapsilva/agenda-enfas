<?php

namespace Tests\Feature;

use App\Models\ClinicalProtocolTemplate;
use App\Models\ClinicalProtocolTemplateItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClinicalWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped(
            'Modulo clinico legado fora do escopo da Agenda ENFAS.'
        );
    }

    use RefreshDatabase;

    public function test_structured_evolution_is_signed_with_integrity_hash(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        [$appointmentId] = $this->makeAppointment();

        $this->actingAs($admin)
            ->post(route('clinical-workflow.evolutions.store', $appointmentId), [
                'format' => 'soap',
                'subjective' => 'Relato subjetivo.',
                'objective' => 'Dados objetivos.',
                'assessment' => 'Avaliação profissional.',
                'plan' => 'Plano registrado.',
            ])
            ->assertSessionHasNoErrors();

        $evolution = DB::table('appointment_clinical_evolutions')->first();

        $this->assertNotNull($evolution);
        $this->assertSame('soap', $evolution->format);
        $this->assertSame(64, strlen((string) $evolution->integrity_hash));

        $this->assertDatabaseHas('patient_clinical_events', [
            'event_type' => 'structured_evolution_signed',
            'title' => 'Evolução clínica assinada',
        ]);
    }

    public function test_protocol_can_be_started_answered_and_completed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        [$appointmentId] = $this->makeAppointment('Protocolo');

        $template = ClinicalProtocolTemplate::create([
            'name' => 'Protocolo de teste',
            'description' => 'Teste automatizado',
            'is_active' => true,
        ]);

        $itemA = ClinicalProtocolTemplateItem::create([
            'template_id' => $template->id,
            'label' => 'Item obrigatório A',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $itemB = ClinicalProtocolTemplateItem::create([
            'template_id' => $template->id,
            'label' => 'Item obrigatório B',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->post(route('clinical-workflow.protocols.start', $appointmentId), [
                'template_id' => $template->id,
            ])
            ->assertSessionHasNoErrors();

        $run = DB::table('appointment_protocol_runs')->first();
        $this->assertNotNull($run);

        $this->actingAs($admin)
            ->patch(route('clinical-workflow.protocols.update', [$appointmentId, $run->id]), [
                'responses' => [
                    $itemA->id => ['value' => 'done', 'notes' => 'Conferido'],
                    $itemB->id => ['value' => 'not_applicable', 'notes' => 'Não aplicável'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('clinical-workflow.protocols.complete', [$appointmentId, $run->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointment_protocol_runs', [
            'id' => $run->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('patient_clinical_events', [
            'event_type' => 'protocol_completed',
            'title' => 'Protocolo concluído',
        ]);
    }

    private function makeAppointment(string $suffix = 'Evolucao'): array
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
