<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'check_in_completed_at')) {
                $table->timestamp('check_in_completed_at')->nullable();
            }
            if (! Schema::hasColumn('appointments', 'satisfaction_score')) {
                $table->unsignedTinyInteger('satisfaction_score')->nullable();
            }
            if (! Schema::hasColumn('appointments', 'satisfaction_comment')) {
                $table->text('satisfaction_comment')->nullable();
            }
            if (! Schema::hasColumn('appointments', 'satisfaction_at')) {
                $table->timestamp('satisfaction_at')->nullable();
            }
            if (! Schema::hasColumn('appointments', 'return_due_at')) {
                $table->date('return_due_at')->nullable();
            }
            if (! Schema::hasColumn('appointments', 'telehealth_url')) {
                $table->string('telehealth_url', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Migração aditiva de produção: não removemos dados automaticamente.
    }
};
