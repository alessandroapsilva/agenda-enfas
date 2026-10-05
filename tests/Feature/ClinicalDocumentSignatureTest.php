<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicalDocumentSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_is_signed_through_signature_provider_and_locked(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Documento',
            'phone' => '11999999999',
            'rgea_number' => 'RGEA-DOC-001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('clinical-documents.store'), [
                'patient_id' => $patientId,
                'document_type' => 'declaration',
                'title' => 'Declaração de teste',
                'content' => 'Conteúdo clínico administrativo para assinatura.',
            ])
            ->assertRedirect();

        $document = DB::table('clinical_documents')->first();

        $this->assertNotNull($document);
        $this->assertSame('draft', $document->status);

        $this->actingAs($admin)
            ->post(route('clinical-documents.sign', $document->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clinical_documents', [
            'id' => $document->id,
            'status' => 'signed',
            'external_provider' => 'agenda_enfas',
        ]);

        $signature = DB::table('clinical_document_signatures')
            ->where('clinical_document_id', $document->id)
            ->first();

        $this->assertNotNull($signature);
        $this->assertSame('electronic', $signature->signature_type);
        $this->assertSame('agenda_enfas', $signature->provider);
        $this->assertSame(64, strlen((string) $signature->document_hash));

        $this->actingAs($admin)
            ->patch(route('clinical-documents.update', $document->id), [
                'title' => 'Tentativa',
                'content' => 'Não deve alterar',
            ])
            ->assertSessionHasErrors('document');
    }
}
