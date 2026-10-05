<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_conversations')) {
            Schema::table('wa_conversations', function (Blueprint $table) {
                if (! Schema::hasColumn('wa_conversations', 'lead_stage')) {
                    $table->string('lead_stage', 40)->default('new')->index();
                }
                if (! Schema::hasColumn('wa_conversations', 'priority')) {
                    $table->string('priority', 20)->default('normal')->index();
                }
                if (! Schema::hasColumn('wa_conversations', 'tags')) {
                    $table->json('tags')->nullable();
                }
                if (! Schema::hasColumn('wa_conversations', 'first_inbound_at')) {
                    $table->timestamp('first_inbound_at')->nullable()->index();
                }
                if (! Schema::hasColumn('wa_conversations', 'first_response_at')) {
                    $table->timestamp('first_response_at')->nullable()->index();
                }
            });
        }

        if (! Schema::hasTable('clinic_tasks')) {
            Schema::create('clinic_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('conversation_id')->nullable()->constrained('wa_conversations')->nullOnDelete();
                $table->string('title', 180);
                $table->text('notes')->nullable();
                $table->string('priority', 20)->default('normal')->index();
                $table->string('status', 20)->default('open')->index();
                $table->timestamp('due_at')->nullable()->index();
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quick_replies')) {
            Schema::create('quick_replies', function (Blueprint $table) {
                $table->id();
                $table->string('title', 120);
                $table->string('shortcut', 60)->nullable()->unique();
                $table->text('body');
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_replies');
        Schema::dropIfExists('clinic_tasks');

        if (Schema::hasTable('wa_conversations')) {
            Schema::table('wa_conversations', function (Blueprint $table) {
                foreach (['lead_stage','priority','tags','first_inbound_at','first_response_at'] as $column) {
                    if (Schema::hasColumn('wa_conversations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
