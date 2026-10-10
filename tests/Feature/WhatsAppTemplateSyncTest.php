<?php

namespace Tests\Feature;

use App\Models\MetaIntegration;
use App\Models\WaTemplate;
use App\Services\Enfas\MetaWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTemplateSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_does_not_reactivate_locally_archived_template(): void
    {
        MetaIntegration::create([
            'name' => 'Meta Teste',
            'waba_id' => '123456',
            'phone_number_id' => '999999',
            'business_id' => '555555',
            'graph_version' => 'v25.0',
            'access_token' => 'token-test',
            'app_secret' => 'secret-test',
            'verify_token' => 'verify-test',
            'is_active' => true,
        ]);

        $template = WaTemplate::create([
            'meta_template_id' => 'remote-old',
            'name' => 'lembrete_agendamento',
            'purpose' => 'reminder',
            'category' => 'UTILITY',
            'language' => 'pt_BR',
            'status' => 'APPROVED',
            'header_text' => null,
            'body' => 'Olá {{1}}',
            'footer' => 'ENFAS',
            'buttons' => [],
            'variable_keys' => ['paciente_nome'],
            'sample_values' => ['João'],
            'is_active' => false,
            'archived_at' => now()->subDay(),
        ]);

        Http::fake([
            'https://graph.facebook.com/*' =>
                Http::response([
                    'data' => [
                        [
                            'id' => 'remote-new',
                            'name' => 'lembrete_agendamento',
                            'language' => 'pt_BR',
                            'category' => 'UTILITY',
                            'status' => 'APPROVED',
                            'components' => [
                                [
                                    'type' => 'BODY',
                                    'text' => 'Olá {{1}}',
                                ],
                            ],
                        ],
                    ],
                    'paging' => [],
                ], 200),
        ]);

        app(
            MetaWhatsAppService::class
        )->syncTemplates();

        $template->refresh();

        $this->assertSame(
            'remote-new',
            $template->meta_template_id
        );

        $this->assertSame(
            'APPROVED',
            $template->status
        );

        $this->assertFalse(
            (bool) $template->is_active
        );

        $this->assertNotNull(
            $template->archived_at
        );
    }
}
