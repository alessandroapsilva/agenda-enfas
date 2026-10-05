<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_clinical_records', function (Blueprint $table) {
            $table->text('physical_exam')->nullable()->after('history');
            $table->text('clinical_impression')->nullable()->after('assessment');
            $table->text('care_plan_summary')->nullable()->after('follow_up_plan');
        });

        Schema::create('patient_clinical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained()->restrictOnDelete();
            $table->text('chronic_conditions')->nullable();
            $table->text('surgeries')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();
            $table->text('immunizations')->nullable();
            $table->text('other_history')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('substance', 180);
            $table->text('reaction')->nullable();
            $table->string('severity', 30)->default('unknown')->index();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('recorded_at')->useCurrent()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status'], 'allergy_patient_status_idx');
        });

        Schema::create('patient_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code_system', 30)->nullable();
            $table->string('code', 40)->nullable();
            $table->string('description', 255);
            $table->string('status', 30)->default('active')->index();
            $table->date('onset_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status'], 'problem_patient_status_idx');
            $table->index(['code_system', 'code'], 'problem_code_idx');
        });

        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medication_name', 180);
            $table->string('concentration', 120)->nullable();
            $table->string('route', 120)->nullable();
            $table->text('directions')->nullable();
            $table->date('started_on')->nullable();
            $table->date('stopped_on')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('stop_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('stopped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status'], 'med_patient_status_idx');
        });

        Schema::create('appointment_clinical_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_record_id')->nullable()->constrained('appointment_clinical_records')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('professional_id')->constrained()->restrictOnDelete();
            $table->string('scale_key', 80)->nullable()->index();
            $table->string('scale_name', 160);
            $table->decimal('score', 10, 2)->nullable();
            $table->string('classification', 160)->nullable();
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['appointment_id', 'recorded_at'], 'scale_appointment_date_idx');
        });

        Schema::create('clinical_care_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_record_id')->nullable()->constrained('appointment_clinical_records')->nullOnDelete();
            $table->foreignId('responsible_professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->string('goal', 500);
            $table->text('actions');
            $table->date('target_date')->nullable()->index();
            $table->string('status', 30)->default('planned')->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['appointment_id', 'status'], 'care_plan_appointment_status_idx');
        });

        Schema::create('patient_clinical_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 80)->index();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['patient_id', 'occurred_at'], 'clinical_event_patient_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_events');
        Schema::dropIfExists('clinical_care_plans');
        Schema::dropIfExists('appointment_clinical_scales');
        Schema::dropIfExists('patient_medications');
        Schema::dropIfExists('patient_problems');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_clinical_histories');

        Schema::table('appointment_clinical_records', function (Blueprint $table) {
            $table->dropColumn(['physical_exam', 'clinical_impression', 'care_plan_summary']);
        });
    }
};
