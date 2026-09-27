<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (! Schema::hasColumn('patients', 'rgea_number')) {
                    $table->string('rgea_number', 40)->nullable()->unique();
                }
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (! Schema::hasColumn('appointments', 'appointment_type')) {
                    $table->string('appointment_type', 40)->default('care');
                }
                if (! Schema::hasColumn('appointments', 'medication_name')) {
                    $table->string('medication_name', 255)->nullable();
                }
                if (! Schema::hasColumn('appointments', 'medication_quantity')) {
                    $table->string('medication_quantity', 120)->nullable();
                }
                if (! Schema::hasColumn('appointments', 'medication_notes')) {
                    $table->text('medication_notes')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
