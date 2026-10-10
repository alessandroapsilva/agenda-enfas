<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Services\Enfas\WhatsAppDispatchPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppDispatchPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_dedupe_is_independent_from_trigger_path(): void
    {
        $policy = app(
            WhatsAppDispatchPolicy::class
        );

        $appointment = (object) [
            'id' => 77,
            'start_at' => '2026-10-20 14:30:00',
        ];

        $created = $policy->canonicalDedupeKey(
            $appointment,
            'confirmation',
            'appointment_created',
            0
        );

        $before = $policy->canonicalDedupeKey(
            $appointment,
            'confirmation',
            'appointment_before',
            120
        );

        $this->assertSame(
            $created,
            $before
        );
    }

    public function test_reminders_keep_different_offsets_distinct(): void
    {
        $policy = app(
            WhatsAppDispatchPolicy::class
        );

        $appointment = (object) [
            'id' => 91,
            'start_at' => '2026-10-21 09:00:00',
        ];

        $day = $policy->canonicalDedupeKey(
            $appointment,
            'reminder',
            'appointment_before',
            1440
        );

        $twoHours = $policy->canonicalDedupeKey(
            $appointment,
            'reminder',
            'appointment_before',
            120
        );

        $this->assertNotSame(
            $day,
            $twoHours
        );
    }

    public function test_only_time_based_events_belong_to_scheduler(): void
    {
        $policy = app(
            WhatsAppDispatchPolicy::class
        );

        $this->assertTrue(
            $policy->isScheduledEvent(
                'appointment_before'
            )
        );

        $this->assertTrue(
            $policy->isScheduledEvent(
                'appointment_return_due'
            )
        );

        foreach ([
            'appointment_created',
            'appointment_confirmed',
            'appointment_cancelled',
            'appointment_completed',
            'appointment_rescheduled',
        ] as $event) {
            $this->assertFalse(
                $policy->isScheduledEvent(
                    $event
                )
            );
        }
    }

    public function test_manual_text_is_idempotent_inside_same_minute(): void
    {
        Carbon::setTestNow(
            '2026-10-10 12:34:10'
        );

        $policy = app(
            WhatsAppDispatchPolicy::class
        );

        $first = $policy->manualTextDedupeKey(
            10,
            20,
            'Confirmando seu horário.'
        );

        Carbon::setTestNow(
            '2026-10-10 12:34:59'
        );

        $sameMinute = $policy->manualTextDedupeKey(
            10,
            20,
            'Confirmando seu horário.'
        );

        Carbon::setTestNow(
            '2026-10-10 12:35:01'
        );

        $nextMinute = $policy->manualTextDedupeKey(
            10,
            20,
            'Confirmando seu horário.'
        );

        $this->assertSame(
            $first,
            $sameMinute
        );

        $this->assertNotSame(
            $first,
            $nextMinute
        );

        Carbon::setTestNow();
    }

    public function test_settings_and_patient_opt_out_control_automation(): void
    {
        $patientId = DB::table(
            'patients'
        )->insertGetId([
            'name' => 'Paciente Teste',
            'phone' => '11999990000',
            'email' => null,
            'contact_consent' => true,
            'do_not_contact' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $professionalId = DB::table(
            'professionals'
        )->insertGetId([
            'name' => 'Profissional Teste',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table(
            'services'
        )->insertGetId([
            'name' => 'Serviço Teste',
            'duration_minutes' => 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = DB::table(
            'appointments'
        )->insertGetId([
            'code' => 'AG-TEST01',
            'public_token' => (string) Str::uuid(),
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
            'duration_minutes' => 30,
            'status' => 'awaiting_confirmation',
            'confirmation_status' => 'pending',
            'source' => 'internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $policy = app(
            WhatsAppDispatchPolicy::class
        );

        AppSetting::setValue(
            'agenda',
            'confirmation_enabled',
            false,
            'boolean'
        );

        $this->assertFalse(
            $policy->automatedMessageAllowed(
                $appointmentId,
                'confirmation'
            )
        );

        AppSetting::setValue(
            'agenda',
            'confirmation_enabled',
            true,
            'boolean'
        );

        $this->assertTrue(
            $policy->automatedMessageAllowed(
                $appointmentId,
                'confirmation'
            )
        );

        DB::table('patients')
            ->where(
                'id',
                $patientId
            )
            ->update([
                'do_not_contact' => true,
            ]);

        $this->assertFalse(
            $policy->automatedMessageAllowed(
                $appointmentId,
                'confirmation'
            )
        );
    }
}
