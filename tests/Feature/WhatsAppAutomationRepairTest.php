<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WhatsAppAutomationRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_keeps_one_equivalent_rule_and_preserves_distinct_offsets(): void
    {
        $templateId = DB::table(
            'wa_templates'
        )->insertGetId([
            'name' => 'lembrete_teste',
            'purpose' => 'reminder',
            'category' => 'UTILITY',
            'language' => 'pt_BR',
            'status' => 'APPROVED',
            'body' => 'Lembrete de teste',
            'is_active' => true,
            'archived_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $first = DB::table(
            'wa_automations'
        )->insertGetId([
            'name' => 'Lembrete 24h A',
            'trigger_event' => 'appointment_before',
            'offset_minutes' => 1440,
            'template_id' => $templateId,
            'wa_template_id' => $templateId,
            'service_id' => null,
            'send_once' => true,
            'retry_count' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $duplicate = DB::table(
            'wa_automations'
        )->insertGetId([
            'name' => 'Lembrete 24h B',
            'trigger_event' => 'appointment_before',
            'offset_minutes' => 1440,
            'template_id' => $templateId,
            'wa_template_id' => $templateId,
            'service_id' => null,
            'send_once' => true,
            'retry_count' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $twoHours = DB::table(
            'wa_automations'
        )->insertGetId([
            'name' => 'Lembrete 2h',
            'trigger_event' => 'appointment_before',
            'offset_minutes' => 120,
            'template_id' => $templateId,
            'wa_template_id' => $templateId,
            'service_id' => null,
            'send_once' => true,
            'retry_count' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan(
            'enfas:repair-whatsapp-automations',
            ['--apply' => true]
        )->assertExitCode(0);

        $this->assertDatabaseHas(
            'wa_automations',
            [
                'id' => $first,
                'is_active' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'wa_automations',
            [
                'id' => $duplicate,
                'is_active' => 0,
            ]
        );

        $this->assertDatabaseHas(
            'wa_automations',
            [
                'id' => $twoHours,
                'is_active' => 1,
            ]
        );
    }

    public function test_repair_pauses_active_rule_with_missing_template(): void
    {
        $automationId = DB::table(
            'wa_automations'
        )->insertGetId([
            'name' => 'Regra quebrada',
            'trigger_event' => 'appointment_before',
            'offset_minutes' => 120,
            'template_id' => 999999,
            'wa_template_id' => 999999,
            'service_id' => null,
            'send_once' => true,
            'retry_count' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan(
            'enfas:repair-whatsapp-automations',
            ['--apply' => true]
        )->assertExitCode(0);

        $this->assertDatabaseHas(
            'wa_automations',
            [
                'id' => $automationId,
                'is_active' => 0,
            ]
        );
    }
}
