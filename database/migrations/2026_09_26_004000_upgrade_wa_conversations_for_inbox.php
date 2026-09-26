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
                if (! Schema::hasColumn('wa_conversations', 'mode')) {
                    $table->string('mode', 20)->default('bot')->after('status');
                }
                if (! Schema::hasColumn('wa_conversations', 'unread_count')) {
                    $table->unsignedInteger('unread_count')->default(0)->after('mode');
                }
                if (! Schema::hasColumn('wa_conversations', 'last_inbound_at')) {
                    $table->timestamp('last_inbound_at')->nullable()->after('last_message_at');
                }
                if (! Schema::hasColumn('wa_conversations', 'last_outbound_at')) {
                    $table->timestamp('last_outbound_at')->nullable()->after('last_inbound_at');
                }
                if (! Schema::hasColumn('wa_conversations', 'human_taken_at')) {
                    $table->timestamp('human_taken_at')->nullable()->after('assigned_user_id');
                }
                if (! Schema::hasColumn('wa_conversations', 'closed_at')) {
                    $table->timestamp('closed_at')->nullable()->after('expires_at');
                }
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
