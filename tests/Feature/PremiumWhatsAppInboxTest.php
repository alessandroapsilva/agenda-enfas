<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaMessage;
use App\Services\Enfas\AvailabilityService;
use App\Services\Enfas\PatientNotificationService;
use App\Services\Enfas\ProfessionalNotificationService;
use App\Services\Enfas\WhatsAppAutomationEngine;
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
            Mockery::mock(WhatsAppAutomationEngine::class),
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
            Mockery::mock(WhatsAppAutomationEngine::class),
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
