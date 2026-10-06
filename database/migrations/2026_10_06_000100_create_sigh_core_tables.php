<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->string('name',180);
            $t->string('legal_name',220)->nullable(); $t->string('document',30)->nullable()->index();
            $t->boolean('active')->default(true); $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('facilities', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('organization_id')->index();
            $t->string('name',180); $t->string('code',40)->nullable();
            $t->string('type',40)->default('clinic'); $t->boolean('active')->default(true);
            $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('professionals', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('organization_id')->index();
            $t->foreignId('user_id')->nullable()->index(); $t->string('name',180);
            $t->string('council',20)->nullable(); $t->string('council_number',40)->nullable();
            $t->string('council_state',4)->nullable(); $t->string('occupation_code',30)->nullable();
            $t->boolean('active')->default(true); $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('patients', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('organization_id')->index();
            $t->string('medical_record_number',40); $t->string('name',180)->index();
            $t->string('social_name',180)->nullable(); $t->string('cpf',14)->nullable()->index();
            $t->string('cns',20)->nullable()->index(); $t->date('birth_date')->nullable();
            $t->string('sex',30)->nullable(); $t->string('phone',30)->nullable();
            $t->string('email',180)->nullable(); $t->string('mother_name',180)->nullable();
            $t->json('metadata')->nullable(); $t->timestamps();
            $t->unique(['organization_id','medical_record_number']);
        });

        Schema::create('encounters', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->index();
            $t->ulid('facility_id')->nullable()->index(); $t->ulid('professional_id')->nullable()->index();
            $t->string('type',40)->default('outpatient'); $t->string('status',30)->default('waiting')->index();
            $t->timestamp('checkin_at')->nullable(); $t->timestamp('started_at')->nullable();
            $t->timestamp('ended_at')->nullable(); $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('clinical_notes', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('encounter_id')->index(); $t->ulid('patient_id')->index();
            $t->ulid('professional_id')->nullable()->index(); $t->string('type',40)->default('evolution');
            $t->longText('content'); $t->string('status',30)->default('draft');
            $t->timestamp('signed_at')->nullable(); $t->string('signature_hash',128)->nullable(); $t->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->index();
            $t->ulid('encounter_id')->nullable()->index(); $t->ulid('prescriber_id')->nullable()->index();
            $t->string('status',30)->default('draft')->index(); $t->timestamp('issued_at')->nullable();
            $t->timestamp('signed_at')->nullable(); $t->string('signature_provider',80)->nullable();
            $t->string('signature_hash',128)->nullable(); $t->text('notes')->nullable(); $t->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('prescription_id')->index();
            $t->ulid('medication_id')->nullable()->index(); $t->string('medication_name',220);
            $t->string('dose',100)->nullable(); $t->string('route',80)->nullable();
            $t->string('frequency',100)->nullable(); $t->unsignedInteger('duration_days')->nullable();
            $t->decimal('quantity',14,3)->nullable(); $t->text('instructions')->nullable();
            $t->string('status',30)->default('active'); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items'); Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('clinical_notes'); Schema::dropIfExists('encounters');
        Schema::dropIfExists('patients'); Schema::dropIfExists('professionals');
        Schema::dropIfExists('facilities'); Schema::dropIfExists('organizations');
    }
};
