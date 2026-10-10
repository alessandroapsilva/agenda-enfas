<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaMessage;
use App\Services\Enfas\AvailabilityService;
use App\Services\Enfas\PatientNotificationService;
use App\Services\Enfas\ProfessionalNotificationService;
use App\Services\Enfas\WhatsAppConversationEngine;
use App\Services\Enfas\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PremiumWhatsAppInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_plain_inbound_message_creates_one_unread_conversation(): void
    {
        $engine = new WhatsAppConversationEngine(
            Mockery::mock(AvailabilityService::class),
            Mockery::mock(PatientNotificationService::class),
            Mockery::mock(ProfessionalNotificationService::class),
            Mockery::mock(WaitlistService::class),
        );

        $engine->handle([
            'id' => 'wamid.test.1',
            'from' => '5511999999999',
            'type' => 'text',
            'text' => ['body' => 'Olá'],
        ]);

        $conversation = WaConversation::firstOrFail();

        $this->assertSame('5511999999999', $conversation->phone);
        $this->assertSame('bot', $conversation->mode);
        $this->assertSame(1, $conversation->unread_count);
        $this->assertNotNull($conversation->first_inbound_at);

        $message = WaMessage::firstOrFail();

        $this->assertSame('inbound', $message->direction);
        $this->assertSame('Olá', $message->body);
        $this->assertSame('wamid.test.1', $message->meta_message_id);
    }

    public function test_duplicate_meta_message_is_idempotent(): void
    {
        $engine = new WhatsAppConversationEngine(
            Mockery::mock(AvailabilityService::class),
            Mockery::mock(PatientNotificationService::class),
            Mockery::mock(ProfessionalNotificationService::class),
            Mockery::mock(WaitlistService::class),
        );

        $incoming = [
            'id' => 'wamid.test.2',
            'from' => '5511888888888',
            'type' => 'text',
            'text' => ['body' => 'Teste'],
        ];

        $engine->handle($incoming);
        $engine->handle($incoming);

        $this->assertSame(1, WaMessage::count());
        $this->assertSame(1, WaConversation::count());
        $this->assertSame(1, WaConversation::firstOrFail()->unread_count);
    }

    public function test_plain_text_opt_out_blocks_future_automatic_contact(): void
    {
        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Opt Out',
            'phone' => '5511999990001',
            'email' => null,
            'contact_consent' => true,
            'do_not_contact' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        WaConversation::create([
            'phone' => '5511999990001',
            'patient_id' => $patientId,
            'status' => 'active',
            'mode' => 'bot',
            'state' => 'IDLE',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        $notifications = Mockery::mock(
            PatientNotificationService::class
        );

        $notifications
            ->shouldReceive('contactPreferenceChanged')
            ->once()
            ->with(
                $patientId,
                '5511999990001',
                false
            );

        $engine = new WhatsAppConversationEngine(
            Mockery::mock(AvailabilityService::class),
            $notifications,
            Mockery::mock(ProfessionalNotificationService::class),
            Mockery::mock(WaitlistService::class),
        );

        $engine->handle([
            'id' => 'wamid.optout.1',
            'from' => '5511999990001',
            'type' => 'text',
            'text' => [
                'body' => 'PARAR',
            ],
        ]);

        $this->assertDatabaseHas('patients', [
            'id' => $patientId,
            'contact_consent' => 0,
            'do_not_contact' => 1,
            'contact_consent_source' => 'whatsapp_opt_out',
        ]);
    }

    public function test_plain_text_opt_in_reenables_contact(): void
    {
        $patientId = DB::table('patients')->insertGetId([
            'name' => 'Paciente Opt In',
            'phone' => '5511999990002',
            'email' => null,
            'contact_consent' => false,
            'do_not_contact' => true,
            'contact_consent_source' => 'whatsapp_opt_out',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        WaConversation::create([
            'phone' => '5511999990002',
            'patient_id' => $patientId,
            'status' => 'active',
            'mode' => 'bot',
            'state' => 'IDLE',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        $notifications = Mockery::mock(
            PatientNotificationService::class
        );

        $notifications
            ->shouldReceive('contactPreferenceChanged')
            ->once()
            ->with(
                $patientId,
                '5511999990002',
                true
            );

        $engine = new WhatsAppConversationEngine(
            Mockery::mock(AvailabilityService::class),
            $notifications,
            Mockery::mock(ProfessionalNotificationService::class),
            Mockery::mock(WaitlistService::class),
        );

        $engine->handle([
            'id' => 'wamid.optin.1',
            'from' => '5511999990002',
            'type' => 'text',
            'text' => [
                'body' => 'ATIVAR',
            ],
        ]);

        $this->assertDatabaseHas('patients', [
            'id' => $patientId,
            'contact_consent' => 1,
            'do_not_contact' => 0,
            'contact_consent_source' => 'whatsapp_opt_in',
        ]);
    }

    public function test_admin_can_manage_conversation_context_and_follow_up_task(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $conversation = WaConversation::create([
            'phone' => '5511777777777',
            'status' => 'active',
            'mode' => 'human',
            'unread_count' => 0,
            'lead_stage' => 'new',
            'priority' => 'normal',
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('enfas.whatsapp.thread.context', $conversation), [
                'lead_stage' => 'qualified',
                'priority' => 'urgent',
                'tags' => 'retorno, particular, retorno',
            ])
            ->assertSessionHasNoErrors();

        $conversation->refresh();

        $this->assertSame('qualified', $conversation->lead_stage);
        $this->assertSame('urgent', $conversation->priority);
        $this->assertSame(['retorno', 'particular'], $conversation->tags);

        $this->actingAs($admin)
            ->post(route('enfas.whatsapp.thread.tasks.store', $conversation), [
                'title' => 'Retornar para o paciente',
                'priority' => 'high',
                'due_at' => now()->addHour()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasNoErrors();

        $task = DB::table('clinic_tasks')->first();

        $this->assertNotNull($task);
        $this->assertSame($conversation->id, (int) $task->conversation_id);
        $this->assertSame('open', $task->status);

        $this->actingAs($admin)
            ->patch(route('enfas.whatsapp.tasks.complete', $task->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clinic_tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_create_quick_reply(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('enfas.whatsapp.quick-replies.store'), [
                'title' => 'Confirmação humana',
                'shortcut' => 'confirmar',
                'body' => 'Olá! Posso confirmar seu horário?',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('quick_replies', [
            'shortcut' => 'confirmar',
            'is_active' => 1,
        ]);
    }

}
