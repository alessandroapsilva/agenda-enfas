<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sigh_medications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->string('active_ingredient', 180)->nullable();
            $table->string('presentation', 160)->nullable();
            $table->string('concentration', 120)->nullable();
            $table->string('pharmaceutical_form', 120)->nullable();
            $table->boolean('requires_special_control')->default(false)->index();
            $table->string('control_category', 80)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sigh_patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('sigh_medications')->nullOnDelete();
            $table->string('medication_name', 180);
            $table->string('dosage', 120)->nullable();
            $table->string('route', 80)->nullable();
            $table->string('frequency', 120)->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->string('prescriber_name', 160)->nullable();
            $table->string('prescriber_registry', 80)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sigh_pmc_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_medication_id')->nullable()->constrained('sigh_patient_medications')->nullOnDelete();
            $table->decimal('quantity_at_home', 12, 3)->nullable();
            $table->decimal('daily_consumption', 12, 3)->nullable();
            $table->string('unit', 40)->nullable();
            $table->date('last_delivery_at')->nullable();
            $table->date('estimated_end_at')->nullable()->index();
            $table->date('next_supply_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sigh_lme_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('sigh_medications')->nullOnDelete();
            $table->string('medication_name', 180);
            $table->string('cid10', 20)->nullable()->index();
            $table->string('diagnosis', 255)->nullable();
            $table->string('prescriber_name', 160)->nullable();
            $table->string('prescriber_registry', 80)->nullable();
            $table->date('requested_at')->nullable()->index();
            $table->string('protocol_number', 100)->nullable()->index();
            $table->string('status', 40)->default('draft')->index();
            $table->date('valid_until')->nullable()->index();
            $table->date('renewal_due_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sigh_apac_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('procedure_code', 30)->nullable()->index();
            $table->string('procedure_name', 255);
            $table->string('cid10', 20)->nullable()->index();
            $table->string('authorization_number', 100)->nullable()->index();
            $table->string('competence', 7)->nullable()->index();
            $table->date('authorized_from')->nullable();
            $table->date('authorized_until')->nullable()->index();
            $table->string('establishment', 180)->nullable();
            $table->string('professional_name', 160)->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sigh_apac_authorizations');
        Schema::dropIfExists('sigh_lme_requests');
        Schema::dropIfExists('sigh_pmc_controls');
        Schema::dropIfExists('sigh_patient_medications');
        Schema::dropIfExists('sigh_medications');
    }
};
