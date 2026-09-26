<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('professional_availabilities')) {
            Schema::create('professional_availabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->time('break_start')->nullable();
                $table->time('break_end')->nullable();
                $table->unsignedBigInteger('location_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['professional_id', 'day_of_week', 'is_active'], 'prof_availability_lookup');
            });
        }

        if (! Schema::hasTable('appointment_series')) {
            Schema::create('appointment_series', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
                $table->foreignId('professional_id')->constrained('professionals')->restrictOnDelete();
                $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
                $table->string('frequency', 30);
                $table->unsignedSmallInteger('interval')->default(1);
                $table->json('week_days')->nullable();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->unsignedInteger('max_occurrences')->nullable();
                $table->string('status', 30)->default('active');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['professional_id', 'status']);
                $table->index(['patient_id', 'status']);
            });
        }

        if (! Schema::hasTable('slot_reservations')) {
            Schema::create('slot_reservations', function (Blueprint $table) {
                $table->id();
                $table->uuid('token')->unique();
                $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('status', 30)->default('held');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['professional_id', 'starts_at', 'ends_at'], 'slot_reservation_lookup');
                $table->index(['status', 'expires_at']);
            });
        }

        if (! Schema::hasTable('wa_conversations')) {
            Schema::create('wa_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_id')->nullable();
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->string('phone', 40);
                $table->string('state', 60)->default('IDLE');
                $table->string('status', 30)->default('active');
                $table->json('context')->nullable();
                $table->unsignedBigInteger('assigned_user_id')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['phone', 'status']);
                $table->index(['appointment_id', 'status']);
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (! Schema::hasColumn('appointments', 'series_id')) {
                    $table->unsignedBigInteger('series_id')->nullable()->after('service_id')->index();
                }
                if (! Schema::hasColumn('appointments', 'series_position')) {
                    $table->unsignedInteger('series_position')->nullable()->after('series_id');
                }
                if (! Schema::hasColumn('appointments', 'rescheduled_from_id')) {
                    $table->unsignedBigInteger('rescheduled_from_id')->nullable()->after('series_position')->index();
                }
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (! Schema::hasColumn('services', 'arrival_minutes')) {
                    $table->unsignedSmallInteger('arrival_minutes')->default(15);
                }
                if (! Schema::hasColumn('services', 'required_documents')) {
                    $table->text('required_documents')->nullable();
                }
                if (! Schema::hasColumn('services', 'preparation_instructions')) {
                    $table->text('preparation_instructions')->nullable();
                }
                if (! Schema::hasColumn('services', 'aftercare_instructions')) {
                    $table->text('aftercare_instructions')->nullable();
                }
                if (! Schema::hasColumn('services', 'allow_online_reschedule')) {
                    $table->boolean('allow_online_reschedule')->default(true);
                }
                if (! Schema::hasColumn('services', 'allow_recurrence')) {
                    $table->boolean('allow_recurrence')->default(true);
                }
            });
        }

        if (Schema::hasTable('professionals')) {
            Schema::table('professionals', function (Blueprint $table) {
                if (! Schema::hasColumn('professionals', 'whatsapp_notifications_enabled')) {
                    $table->boolean('whatsapp_notifications_enabled')->default(true);
                }
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção: não removemos dados automaticamente.
    }
};
