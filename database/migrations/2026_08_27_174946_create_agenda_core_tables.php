<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients')) {
            Schema::create('patients', function (Blueprint $table) {
                $table->id();

                $table->string('name', 160);
                $table->string('phone', 30);
                $table->string('email', 190)->nullable();

                $table->string('cpf', 20)
                    ->nullable()
                    ->unique();

                $table->date('birth_date')->nullable();

                $table->text('notes')->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();

                $table->index('name');
                $table->index('phone');
            });
        }


        if (! Schema::hasTable('professionals')) {
            Schema::create('professionals', function (Blueprint $table) {
                $table->id();

                $table->string('name', 160);
                $table->string('specialty', 160)->nullable();

                $table->string('phone', 30)->nullable();
                $table->string('email', 190)->nullable();

                $table->time('work_start')
                    ->default('08:00:00');

                $table->time('work_end')
                    ->default('18:00:00');

                $table->json('active_days')
                    ->nullable();

                $table->unsignedSmallInteger('slot_interval')
                    ->default(30);

                $table->string('color', 7)
                    ->default('#2563eb');

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();

                $table->index('name');
            });
        }


        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();

                $table->string('name', 160);

                $table->string('code', 60)
                    ->nullable()
                    ->unique();

                $table->unsignedSmallInteger('duration_minutes')
                    ->default(30);

                $table->text('description')
                    ->nullable();

                $table->string('color', 7)
                    ->default('#2563eb');

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();

                $table->index('name');
            });
        }


        if (! Schema::hasTable('professional_service')) {
            Schema::create('professional_service', function (Blueprint $table) {
                $table->id();

                $table->foreignId('professional_id')
                    ->constrained('professionals')
                    ->cascadeOnDelete();

                $table->foreignId('service_id')
                    ->constrained('services')
                    ->cascadeOnDelete();

                $table->unique([
                    'professional_id',
                    'service_id',
                ]);
            });
        }


        if (! Schema::hasTable('custom_fields')) {
            Schema::create('custom_fields', function (Blueprint $table) {
                $table->id();

                $table->string('name', 160);

                $table->string('slug', 160)
                    ->unique();

                $table->string('entity_type', 30)
                    ->default('appointment');

                $table->string('field_type', 30)
                    ->default('text');

                $table->string('placeholder', 255)
                    ->nullable();

                $table->text('help_text')
                    ->nullable();

                $table->json('options')
                    ->nullable();

                $table->boolean('is_required')
                    ->default(false);

                $table->boolean('is_active')
                    ->default(true);

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();

                $table->index([
                    'entity_type',
                    'is_active',
                    'sort_order',
                ]);
            });
        }


        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();

                $table->string('code', 20)
                    ->unique();

                $table->uuid('public_token')
                    ->unique();

                $table->foreignId('patient_id')
                    ->constrained('patients')
                    ->restrictOnDelete();

                $table->foreignId('professional_id')
                    ->constrained('professionals')
                    ->restrictOnDelete();

                $table->foreignId('service_id')
                    ->constrained('services')
                    ->restrictOnDelete();

                $table->dateTime('start_at');
                $table->dateTime('end_at');

                $table->unsignedSmallInteger('duration_minutes');

                $table->string('status', 40)
                    ->default('awaiting_confirmation');

                $table->string('confirmation_status', 40)
                    ->default('pending');

                $table->string('confirmation_channel', 30)
                    ->nullable();

                $table->timestamp('confirmation_requested_at')
                    ->nullable();

                $table->timestamp('confirmed_at')
                    ->nullable();

                $table->timestamp('cancelled_at')
                    ->nullable();

                $table->text('cancellation_reason')
                    ->nullable();

                $table->string('source', 30)
                    ->default('internal');

                $table->text('notes')
                    ->nullable();

                $table->string('whatsapp_message_id', 191)
                    ->nullable();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'professional_id',
                    'start_at',
                    'end_at',
                ]);

                $table->index('status');
                $table->index('start_at');
            });
        }


        if (! Schema::hasTable('custom_field_values')) {
            Schema::create('custom_field_values', function (Blueprint $table) {
                $table->id();

                $table->foreignId('custom_field_id')
                    ->constrained('custom_fields')
                    ->cascadeOnDelete();

                $table->string('entity_type', 30);

                $table->unsignedBigInteger('entity_id');

                $table->longText('value')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'custom_field_id',
                        'entity_type',
                        'entity_id',
                    ],
                    'custom_field_value_unique'
                );

                $table->index([
                    'entity_type',
                    'entity_id',
                ]);
            });
        }


        if (! Schema::hasTable('appointment_events')) {
            Schema::create('appointment_events', function (Blueprint $table) {
                $table->id();

                $table->foreignId('appointment_id')
                    ->constrained('appointments')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('event_type', 50);

                $table->string('title', 190);

                $table->text('description')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamp('occurred_at')
                    ->useCurrent();

                $table->timestamps();

                $table->index([
                    'appointment_id',
                    'occurred_at',
                ]);
            });
        }
    }


    public function down(): void
    {
        Schema::dropIfExists('appointment_events');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('professional_service');
        Schema::dropIfExists('services');
        Schema::dropIfExists('professionals');
        Schema::dropIfExists('patients');
    }
};
