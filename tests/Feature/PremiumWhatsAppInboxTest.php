<?php

namespace Tests\Feature;

use App\Models\WaConversation;
use App\Models\WaMessage;
use App\Services\Enfas\AvailabilityService;
use App\Services\Enfas\PatientNotificationService;
use App\Services\Enfas\ProfessionalNotificationService;
use App\Services\Enfas\WhatsAppAutomationEngine;
use App\Services\Enfas\WhatsAppConversationEngine;
use App\Services\Enfas\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
