<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wa_templates')) {
            Schema::create('wa_templates', function (Blueprint $table) {
                $table->id();
                $table->string('meta_template_id', 120)->nullable();
                $table->string('name', 160);
                $table->string('purpose', 40)->default('general');
                $table->string('category', 40)->default('UTILITY');
                $table->string('language', 20)->default('pt_BR');
                $table->string('status', 40)->default('LOCAL');
                $table->string('header_text', 255)->nullable();
                $table->longText('body');
                $table->string('footer', 255)->nullable();
                $table->json('buttons')->nullable();
                $table->json('variable_keys')->nullable();
                $table->json('sample_values')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
                $table->unique(['name', 'language']);
                $table->index(['purpose', 'status']);
            });
        }

        if (! Schema::hasTable('wa_automations')) {
            Schema::create('wa_automations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->string('trigger_event', 60);
                $table->integer('offset_minutes')->default(0);
                $table->unsignedBigInteger('template_id');
                $table->unsignedBigInteger('service_id')->nullable();
                $table->boolean('send_once')->default(true);
                $table->unsignedTinyInteger('retry_count')->default(3);
                $table->boolean('is_active')->default(false);
                $table->timestamps();
                $table->index(['trigger_event', 'is_active']);
            });
        }

        if (! Schema::hasTable('wa_messages')) {
            Schema::create('wa_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->unsignedBigInteger('patient_id')->nullable();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->unsignedBigInteger('automation_id')->nullable();
                $table->string('direction', 20)->default('outbound');
                $table->string('message_type', 30)->default('template');
                $table->string('meta_message_id', 191)->nullable()->index();
                $table->string('status', 40)->default('queued');
                $table->string('recipient', 40)->nullable();
                $table->longText('body')->nullable();
                $table->json('payload')->nullable();
                $table->string('dedupe_key', 191)->nullable()->unique();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_webhook_events')) {
            Schema::create('wa_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 80)->nullable();
                $table->json('payload');
                $table->boolean('processed')->default(false);
                $table->text('processing_error')->nullable();
                $table->timestamp('received_at')->useCurrent();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('system_alerts')) {
            Schema::create('system_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('title', 190);
                $table->text('message')->nullable();
                $table->string('severity', 30)->default('info');
                $table->string('source_type', 80)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
