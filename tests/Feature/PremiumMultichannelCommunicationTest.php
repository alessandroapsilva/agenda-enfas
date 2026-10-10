<?php

namespace Tests\Feature;

use App\Mail\BrandedAgendaMessage;
use App\Models\AppSetting;
use App\Services\Enfas\CommunicationDeliveryService;
use App\Services\Enfas\CommunicationPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PremiumMultichannelCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_delivery_is_deduplicated_and_audited(): void
    {
        config([
            'services.sms.enabled' => true,
            'services.sms.provider' => 'twilio',
            'services.sms.twilio.account_sid' =>
                'AC123',
            'services.sms.twilio.auth_token' =>
                'token',
            'services.sms.twilio.from' =>
                '+5511999999999',
        ]);

        Http::fake([
            'https://api.twilio.com/*' =>
                Http::response([
                    'sid' => 'SM123',
                ], 201),
        ]);

        $service = app(
            CommunicationDeliveryService::class
        );

        $first = $service->sms(
            appointmentId: null,
            recipientType: 'professional',
            recipientId: 9,
            recipient: '(11) 98888-7777',
            event: 'created',
            body: 'Novo agendamento',
            dedupeKey: 'test:sms:dedupe'
        );

        $second = $service->sms(
            appointmentId: null,
            recipientType: 'professional',
            recipientId: 9,
            recipient: '(11) 98888-7777',
            event: 'created',
            body: 'Novo agendamento',
            dedupeKey: 'test:sms:dedupe'
        );

        $this->assertSame(
            'sent',
            $first->status
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount(
            'communication_deliveries',
            1
        );

        Http::assertSentCount(1);
    }

    public function test_email_delivery_is_deduplicated_and_audited(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' =>
                'agenda@example.com',
            'mail.from.name' =>
                'ENFAS Agenda',
        ]);

        Mail::fake();

        $service = app(
            CommunicationDeliveryService::class
        );

        $first = $service->email(
            appointmentId: null,
            recipientType: 'patient',
            recipientId: 10,
            recipient: 'paciente@example.com',
            event: 'confirmed',
            subject: 'Agendamento confirmado',
            body: 'Seu horário está confirmado.',
            dedupeKey: 'test:email:dedupe'
        );

        $second = $service->email(
            appointmentId: null,
            recipientType: 'patient',
            recipientId: 10,
            recipient: 'paciente@example.com',
            event: 'confirmed',
            subject: 'Agendamento confirmado',
            body: 'Seu horário está confirmado.',
            dedupeKey: 'test:email:dedupe'
        );

        $this->assertSame(
            'sent',
            $first->status
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount(
            'communication_deliveries',
            1
        );

        Mail::assertSent(
            BrandedAgendaMessage::class,
            1
        );
    }

    public function test_communication_policy_is_configurable(): void
    {
        AppSetting::setValue(
            'communication',
            'patient_email_enabled',
            true,
            'boolean'
        );

        AppSetting::setValue(
            'communication',
            'professional_sms_enabled',
            true,
            'boolean'
        );

        $policy = app(
            CommunicationPolicyService::class
        );

        $this->assertTrue(
            $policy->patientEmailEnabled()
        );

        $this->assertTrue(
            $policy->professionalSmsEnabled()
        );

        $this->assertFalse(
            $policy->professionalWhatsAppEnabled()
        );
    }
}
