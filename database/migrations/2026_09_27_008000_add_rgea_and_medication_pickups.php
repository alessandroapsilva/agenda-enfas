<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'rgea_number')) {
            DB::table('patients')
                ->whereNull('rgea_number')
                ->orderBy('id')
                ->get(['id'])
                ->each(function ($patient) {
                    DB::table('patients')
                        ->where('id', $patient->id)
                        ->update([
                            'rgea_number' => 'RGEA-'.str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT),
                        ]);
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
                if (! Schema::hasColumn('appointments', 'pickup_status')) {
                    $table->string('pickup_status', 40)->nullable();
                }
                if (! Schema::hasColumn('appointments', 'pickup_ready_at')) {
                    $table->timestamp('pickup_ready_at')->nullable();
                }
                if (! Schema::hasColumn('appointments', 'pickup_collected_at')) {
                    $table->timestamp('pickup_collected_at')->nullable();
                }
                if (! Schema::hasColumn('appointments', 'pickup_collected_by')) {
                    $table->string('pickup_collected_by', 160)->nullable();
                }
                if (! Schema::hasColumn('appointments', 'pickup_collector_document')) {
                    $table->string('pickup_collector_document', 80)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
