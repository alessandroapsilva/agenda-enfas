<?php

namespace App\Services\Enfas;

use App\Models\AppSetting;
use App\Models\WaMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsAppDispatchPolicy
{
    public function automationCoreKey(
        string $purpose,
        string $event,
        int $offsetMinutes = 0
    ): string {
        $purpose = strtolower(
            trim($purpose)
        );

        if ($purpose === '') {
            $purpose = 'general';
        }

        if ($purpose === 'confirmation') {
            return 'confirmation';
        }

        if ($purpose === 'reminder') {
            return 'reminder:'
                .max(
                    0,
                    $offsetMinutes
                );
        }

        return implode(
            ':',
            [
                $purpose,
                strtolower(
                    trim($event)
                ),
                max(
                    0,
                    $offsetMinutes
                ),
            ]
        );
    }

    public function automationScopesOverlap(
        ?int $firstServiceId,
        ?int $secondServiceId
    ): bool {
        if (! $firstServiceId
            || ! $secondServiceId) {
            return true;
        }

        return $firstServiceId
            === $secondServiceId;
    }

    public function reminderWindow(
        int $offsetMinutes,
        ?Carbon $now = null,
        int $graceMinutes = 5
    ): array {
        $now = $now
            ? $now->copy()
            : now();

        $target = $now->copy()->addMinutes(
            max(
                0,
                $offsetMinutes
            )
        );

        return [
            $target->copy()->subMinutes(
                max(
                    1,
                    $graceMinutes
                )
            ),
            $target->copy()->addMinute(),
        ];
    }

    public function isScheduledEvent(
        string $event
    ): bool {
        return in_array(
            $event,
            [
                'appointment_before',
                'appointment_return_due',
            ],
            true
        );
    }

    public function automatedMessageAllowed(
        int $appointmentId,
        string $purpose
    ): bool {
        $row = DB::table(
            'appointments as a'
        )
            ->leftJoin(
                'patients as p',
                'p.id',
                '=',
                'a.patient_id'
            )
            ->where(
                'a.id',
                $appointmentId
            )
            ->first([
                'a.id',
                'a.status',
                'a.confirmation_status',
                'p.contact_consent',
                'p.do_not_contact',
            ]);

        if (! $row) {
            return false;
        }

        if ((bool) ($row->do_not_contact ?? false)) {
            return false;
        }

        if (isset($row->contact_consent)
            && ! (bool) $row->contact_consent) {
            return false;
        }

        $purpose = strtolower(
            trim($purpose)
        );

        if ($purpose === 'confirmation') {
            if (in_array(
                (string) $row->status,
                [
                    'confirmed',
                    'cancelled',
                    'canceled',
                    'completed',
                    'no_show',
                ],
                true
            )
                || (string) $row->confirmation_status
                    === 'confirmed') {
                return false;
            }

            return (bool) AppSetting::getValue(
                'agenda',
                'confirmation_enabled',
                true
            );
        }

        if ($purpose === 'reminder') {
            if (in_array(
                (string) $row->status,
                [
                    'cancelled',
                    'canceled',
                    'completed',
                    'no_show',
                ],
                true
            )) {
                return false;
            }

            return (bool) AppSetting::getValue(
                'agenda',
                'reminders_enabled',
                true
            );
        }

        return true;
    }

    public function patientAllowsContactByPatientId(
        int $patientId
    ): bool {
        $row = DB::table('patients')
            ->where('id', $patientId)
            ->first([
                'contact_consent',
                'do_not_contact',
            ]);

        if (! $row) {
            return false;
        }

        if ((bool) ($row->do_not_contact ?? false)) {
            return false;
        }

        if (isset($row->contact_consent)
            && ! (bool) $row->contact_consent) {
            return false;
        }

        return true;
    }

    public function hasRecentOutboundMessage(
        int $appointmentId,
        int $minutes = 10
    ): bool {
        return WaMessage::query()
            ->where(
                'appointment_id',
                $appointmentId
            )
            ->where(
                'direction',
                'outbound'
            )
            ->whereIn(
                'status',
                [
                    'queued',
                    'sending',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->where(
                'created_at',
                '>=',
                now()->subMinutes(
                    max(1, $minutes)
                )
            )
            ->exists();
    }

    public function journeyDedupeKey(
        object $appointment,
        string $intent
    ): string {
        $slot = Carbon::parse(
            $appointment->start_at
        )->format('YmdHi');

        $intent = preg_replace(
            '/[^a-z0-9_-]+/',
            '-',
            strtolower(
                trim($intent)
            )
        );

        return implode(
            ':',
            [
                'wa',
                'journey',
                $intent ?: 'event',
                'appointment',
                $appointment->id,
                'slot',
                $slot,
            ]
        );
    }

    public function manualTextDedupeKey(
        ?int $appointmentId,
        ?int $patientId,
        string $body
    ): string {
        return implode(
            ':',
            [
                'wa',
                'manual',
                'text',
                $appointmentId ?: 0,
                $patientId ?: 0,
                substr(
                    sha1(trim($body)),
                    0,
                    16
                ),
                now()->format('YmdHi'),
            ]
        );
    }

    public function manualTemplateDedupeKey(
        int $appointmentId,
        int $templateId
    ): string {
        return implode(
            ':',
            [
                'wa',
                'manual',
                'template',
                $appointmentId,
                $templateId,
                now()->format('YmdHi'),
            ]
        );
    }

    public function patientAllowsContact(
        int $appointmentId
    ): bool {
        $row = DB::table(
            'appointments as a'
        )
            ->leftJoin(
                'patients as p',
                'p.id',
                '=',
                'a.patient_id'
            )
            ->where(
                'a.id',
                $appointmentId
            )
            ->first([
                'p.contact_consent',
                'p.do_not_contact',
            ]);

        if (! $row) {
            return false;
        }

        if ((bool) ($row->do_not_contact ?? false)) {
            return false;
        }

        if (isset($row->contact_consent)
            && ! (bool) $row->contact_consent) {
            return false;
        }

        return true;
    }

    public function canonicalDedupeKey(
        object $appointment,
        string $purpose,
        string $event,
        int $offsetMinutes = 0
    ): string {
        $purpose = strtolower(
            trim($purpose)
        );

        if ($purpose === '') {
            $purpose = 'general';
        }

        $slot = Carbon::parse(
            $appointment->start_at
        )->format('YmdHi');

        if ($purpose === 'confirmation') {
            return implode(
                ':',
                [
                    'wa',
                    'v3',
                    'confirmation',
                    'appointment',
                    $appointment->id,
                    'slot',
                    $slot,
                ]
            );
        }

        if ($purpose === 'reminder') {
            return implode(
                ':',
                [
                    'wa',
                    'v3',
                    'reminder',
                    max(0, $offsetMinutes),
                    'appointment',
                    $appointment->id,
                    'slot',
                    $slot,
                ]
            );
        }

        return implode(
            ':',
            [
                'wa',
                'v3',
                preg_replace(
                    '/[^a-z0-9_-]+/',
                    '-',
                    $purpose
                ),
                preg_replace(
                    '/[^a-z0-9_-]+/',
                    '-',
                    strtolower($event)
                ),
                max(0, $offsetMinutes),
                'appointment',
                $appointment->id,
                'slot',
                $slot,
            ]
        );
    }

    public function existingEquivalentMessage(
        object $appointment,
        string $purpose,
        string $event,
        int $offsetMinutes = 0
    ): ?WaMessage {
        $purpose = strtolower(
            trim($purpose)
        );

        $boundary = $this->lifecycleBoundary(
            $appointment
        );

        $query = DB::table(
            'wa_messages as wm'
        )
            ->leftJoin(
                'wa_templates as wt',
                'wt.id',
                '=',
                'wm.template_id'
            )
            ->leftJoin(
                'wa_automations as wa',
                'wa.id',
                '=',
                'wm.automation_id'
            )
            ->where(
                'wm.appointment_id',
                $appointment->id
            )
            ->where(
                'wm.direction',
                'outbound'
            )
            ->whereIn(
                'wm.status',
                [
                    'queued',
                    'sending',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->where(
                'wm.created_at',
                '>=',
                $boundary
            );

        if ($purpose === 'confirmation') {
            $query->where(
                'wt.purpose',
                'confirmation'
            );
        } elseif ($purpose === 'reminder') {
            $query
                ->where(
                    'wt.purpose',
                    'reminder'
                )
                ->where(
                    'wa.offset_minutes',
                    max(0, $offsetMinutes)
                );
        } else {
            $query
                ->where(
                    'wt.purpose',
                    $purpose
                )
                ->where(
                    'wa.trigger_event',
                    $event
                )
                ->where(
                    'wa.offset_minutes',
                    max(0, $offsetMinutes)
                );
        }

        $id = $query
            ->orderByDesc(
                'wm.id'
            )
            ->value(
                'wm.id'
            );

        return $id
            ? WaMessage::find($id)
            : null;
    }

    public function blocksRetry(
        ?WaMessage $message,
        int $templateId
    ): bool {
        if (! $message) {
            return false;
        }

        if ($message->status !== 'failed') {
            return true;
        }

        return (int) $message->template_id
            === $templateId;
    }

    private function lifecycleBoundary(
        object $appointment
    ): Carbon {
        if (! empty(
            $appointment->rescheduled_at
        )) {
            return Carbon::parse(
                $appointment->rescheduled_at
            );
        }

        if (! empty(
            $appointment->created_at
        )) {
            return Carbon::parse(
                $appointment->created_at
            );
        }

        return now()->subYears(10);
    }
}
