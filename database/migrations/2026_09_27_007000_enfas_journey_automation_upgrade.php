<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (! Schema::hasColumn('appointments', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable();
                }
                if (! Schema::hasColumn('appointments', 'rescheduled_at')) {
                    $table->timestamp('rescheduled_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (! Schema::hasColumn('services', 'return_after_days')) {
                    $table->unsignedSmallInteger('return_after_days')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
