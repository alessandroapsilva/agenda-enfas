<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('appointments')
            && Schema::hasTable('professional_service')
        ) {
            DB::table('appointments')
                ->select([
                    'professional_id',
                    'service_id',
                ])
                ->whereNotNull('professional_id')
                ->whereNotNull('service_id')
                ->distinct()
                ->orderBy('professional_id')
                ->get()
                ->each(function ($row) {
                    DB::table(
                        'professional_service'
                    )->insertOrIgnore([
                        'professional_id' =>
                            $row->professional_id,
                        'service_id' =>
                            $row->service_id,
                    ]);
                });
        }

        if (Schema::hasTable('professionals')) {
            Schema::table('professionals', function (Blueprint $table) {
                if (! Schema::hasColumn('professionals', 'email_notifications_enabled')) {
                    $table->boolean('email_notifications_enabled')
                        ->default(false)
                        ->after('whatsapp_notifications_enabled');
                }

                if (! Schema::hasColumn('professionals', 'sms_notifications_enabled')) {
                    $table->boolean('sms_notifications_enabled')
                        ->default(false)
                        ->after('email_notifications_enabled');
                }
            });
        }

        if (Schema::hasTable('professional_availabilities')) {
            Schema::table('professional_availabilities', function (Blueprint $table) {
                if (! Schema::hasColumn('professional_availabilities', 'slot_minutes')) {
                    $table->unsignedSmallInteger('slot_minutes')
                        ->nullable()
                        ->after('end_time');
                }
            });
        }

        if (! Schema::hasTable('communication_deliveries')) {
            Schema::create('communication_deliveries', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('appointment_id')
                    ->nullable()
                    ->index();

                $table->string('recipient_type', 30);
                $table->unsignedBigInteger('recipient_id')
                    ->nullable();

                $table->string('channel', 20);
                $table->string('event', 60);
                $table->string('recipient', 190);

                $table->string('status', 30)
                    ->default('queued');

                $table->string('dedupe_key', 191)
                    ->unique();

                $table->string('subject', 190)
                    ->nullable();

                $table->text('body')
                    ->nullable();

                $table->string('provider_message_id', 191)
                    ->nullable();

                $table->text('error_message')
                    ->nullable();

                $table->timestamp('sent_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'recipient_type',
                    'recipient_id',
                    'channel',
                ], 'comm_delivery_recipient');

                $table->index([
                    'appointment_id',
                    'event',
                    'channel',
                ], 'comm_delivery_appointment');
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção: não removemos dados automaticamente.
    }
};
