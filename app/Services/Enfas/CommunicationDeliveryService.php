<?php

namespace App\Services\Enfas;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CommunicationDeliveryService
{
    public function __construct(
        private EmailChannelService $email,
        private SmsChannelService $sms
    ) {
    }

    public function email(
        ?int $appointmentId,
        string $recipientType,
        ?int $recipientId,
        string $recipient,
        string $event,
        string $subject,
        string $body,
        string $dedupeKey
    ): ?object {
        return $this->deliver(
            appointmentId: $appointmentId,
            recipientType: $recipientType,
            recipientId: $recipientId,
            channel: 'email',
            event: $event,
            recipient: $recipient,
            subject: $subject,
            body: $body,
            dedupeKey: $dedupeKey,
            sender: function () use (
                $recipient,
                $subject,
                $body
            ) {
                $this->email->send(
                    $recipient,
                    $subject,
                    $body
                );

                return null;
            }
        );
    }

    public function sms(
        ?int $appointmentId,
        string $recipientType,
        ?int $recipientId,
        string $recipient,
        string $event,
        string $body,
        string $dedupeKey
    ): ?object {
        return $this->deliver(
            appointmentId: $appointmentId,
            recipientType: $recipientType,
            recipientId: $recipientId,
            channel: 'sms',
            event: $event,
            recipient: $recipient,
            subject: null,
            body: $body,
            dedupeKey: $dedupeKey,
            sender: fn () =>
                $this->sms->send(
                    $recipient,
                    $body
                )
        );
    }

    private function deliver(
        ?int $appointmentId,
        string $recipientType,
        ?int $recipientId,
        string $channel,
        string $event,
        string $recipient,
        ?string $subject,
        string $body,
        string $dedupeKey,
        callable $sender
    ): ?object {
        if (! Schema::hasTable(
            'communication_deliveries'
        )) {
            return null;
        }

        $existing = DB::table(
            'communication_deliveries'
        )
            ->where(
                'dedupe_key',
                $dedupeKey
            )
            ->first();

        if (
            $existing
            && in_array(
                $existing->status,
                ['sent', 'queued'],
                true
            )
        ) {
            return $existing;
        }

        if (
            $existing
            && $existing->status === 'failed'
            && $existing->updated_at
            && \Illuminate\Support\Carbon::parse(
                $existing->updated_at
            )->gte(
                now()->subMinutes(30)
            )
        ) {
            return $existing;
        }

        $payload = [
            'appointment_id' => $appointmentId,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'channel' => $channel,
            'event' => $event,
            'recipient' => $recipient,
            'status' => 'queued',
            'dedupe_key' => $dedupeKey,
            'subject' => $subject,
            'body' => $body,
            'provider_message_id' => null,
            'error_message' => null,
            'sent_at' => null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table(
                'communication_deliveries'
            )
                ->where(
                    'id',
                    $existing->id
                )
                ->update($payload);

            $id = $existing->id;
        } else {
            $payload['created_at'] =
                now();

            try {
                $id = DB::table(
                    'communication_deliveries'
                )->insertGetId($payload);
            } catch (QueryException $e) {
                $raced = DB::table(
                    'communication_deliveries'
                )
                    ->where(
                        'dedupe_key',
                        $dedupeKey
                    )
                    ->first();

                if ($raced) {
                    return $raced;
                }

                throw $e;
            }
        }

        try {
            $providerId = $sender();

            DB::table(
                'communication_deliveries'
            )
                ->where(
                    'id',
                    $id
                )
                ->update([
                    'status' => 'sent',
                    'provider_message_id' =>
                        $providerId,
                    'error_message' => null,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);

        } catch (Throwable $e) {
            report($e);

            DB::table(
                'communication_deliveries'
            )
                ->where(
                    'id',
                    $id
                )
                ->update([
                    'status' => 'failed',
                    'error_message' =>
                        $e->getMessage(),
                    'updated_at' => now(),
                ]);
        }

        return DB::table(
            'communication_deliveries'
        )->find($id);
    }
}
