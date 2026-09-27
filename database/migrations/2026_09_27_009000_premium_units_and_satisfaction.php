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
                if (! Schema::hasColumn('appointments', 'satisfaction_stars')) {
                    $table->unsignedTinyInteger('satisfaction_stars')->nullable();
                }
            });
        }

        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (! Schema::hasColumn('locations', 'code')) {
                    $table->string('code', 40)->nullable()->unique();
                }
                if (! Schema::hasColumn('locations', 'whatsapp')) {
                    $table->string('whatsapp', 40)->nullable();
                }
                if (! Schema::hasColumn('locations', 'address_number')) {
                    $table->string('address_number', 30)->nullable();
                }
                if (! Schema::hasColumn('locations', 'address_complement')) {
                    $table->string('address_complement', 120)->nullable();
                }
                if (! Schema::hasColumn('locations', 'neighborhood')) {
                    $table->string('neighborhood', 120)->nullable();
                }
                if (! Schema::hasColumn('locations', 'responsible_name')) {
                    $table->string('responsible_name', 160)->nullable();
                }
                if (! Schema::hasColumn('locations', 'opening_hours')) {
                    $table->text('opening_hours')->nullable();
                }
                if (! Schema::hasColumn('locations', 'patient_instructions')) {
                    $table->text('patient_instructions')->nullable();
                }
                if (! Schema::hasColumn('locations', 'is_main')) {
                    $table->boolean('is_main')->default(false)->index();
                }
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
