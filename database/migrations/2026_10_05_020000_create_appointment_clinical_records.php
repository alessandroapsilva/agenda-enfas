<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointment_clinical_records')) {
            Schema::create('appointment_clinical_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('professional_id')->constrained()->cascadeOnDelete();

                $table->string('status', 20)->default('draft')->index();
                $table->unsignedInteger('version')->default(1);

                $table->text('reason_for_visit')->nullable();
                $table->text('history')->nullable();
                $table->json('vitals')->nullable();
                $table->text('assessment')->nullable();
                $table->text('interventions')->nullable();
                $table->text('guidance')->nullable();
                $table->text('evolution')->nullable();
                $table->text('follow_up_plan')->nullable();

                $table->timestamp('started_at')->nullable();
                $table->timestamp('finalized_at')->nullable()->index();
                $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();

                $table->char('integrity_hash', 64)->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['patient_id','status']);
                $table->index(['professional_id','status']);
            });
        }

        if (! Schema::hasTable('appointment_clinical_addenda')) {
            Schema::create('appointment_clinical_addenda', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinical_record_id')->constrained('appointment_clinical_records')->cascadeOnDelete();
                $table->text('body');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('signed_at')->useCurrent();
                $table->timestamps();

                $table->index(['clinical_record_id','signed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_clinical_addenda');
        Schema::dropIfExists('appointment_clinical_records');
    }
};
