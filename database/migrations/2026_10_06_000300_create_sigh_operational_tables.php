<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('organization_id')->index();
            $t->ulid('facility_id')->index(); $t->ulid('patient_id')->index();
            $t->ulid('professional_id')->nullable()->index(); $t->string('type',60)->default('consultation');
            $t->string('status',30)->default('scheduled')->index(); $t->timestamp('starts_at')->index();
            $t->timestamp('ends_at')->nullable(); $t->text('notes')->nullable(); $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('queue_tickets', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('facility_id')->index(); $t->ulid('patient_id')->nullable()->index();
            $t->string('code',20)->index(); $t->string('queue',60)->index(); $t->unsignedSmallInteger('priority')->default(0);
            $t->string('status',30)->default('waiting')->index(); $t->timestamp('called_at')->nullable();
            $t->timestamp('finished_at')->nullable(); $t->timestamps();
        });

        Schema::create('beds', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('facility_id')->index(); $t->string('unit',100)->index();
            $t->string('room',40)->nullable(); $t->string('code',40)->index(); $t->string('type',40)->default('ward');
            $t->string('status',30)->default('available')->index(); $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('admissions', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->index(); $t->ulid('facility_id')->index();
            $t->ulid('bed_id')->nullable()->index(); $t->ulid('professional_id')->nullable()->index();
            $t->string('type',40)->default('inpatient'); $t->string('status',30)->default('active')->index();
            $t->timestamp('admitted_at')->index(); $t->timestamp('discharged_at')->nullable();
            $t->string('discharge_type',60)->nullable(); $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('bed_movements', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('admission_id')->index();
            $t->ulid('from_bed_id')->nullable()->index(); $t->ulid('to_bed_id')->nullable()->index();
            $t->string('reason',160)->nullable(); $t->timestamp('moved_at')->index();
            $t->string('performed_by',80)->nullable(); $t->timestamps();
        });

        Schema::create('nursing_records', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->index();
            $t->ulid('encounter_id')->nullable()->index(); $t->ulid('admission_id')->nullable()->index();
            $t->ulid('professional_id')->nullable()->index(); $t->string('type',50)->index();
            $t->json('measurements')->nullable(); $t->longText('notes')->nullable();
            $t->timestamp('recorded_at')->index(); $t->timestamp('signed_at')->nullable(); $t->timestamps();
        });

        Schema::create('exam_orders', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->index();
            $t->ulid('encounter_id')->nullable()->index(); $t->ulid('requester_id')->nullable()->index();
            $t->string('code',60)->nullable()->index(); $t->string('name',220);
            $t->string('category',60)->nullable()->index(); $t->string('status',30)->default('ordered')->index();
            $t->string('priority',30)->default('routine'); $t->timestamp('ordered_at')->index();
            $t->timestamp('collected_at')->nullable(); $t->timestamps();
        });

        Schema::create('exam_results', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('exam_order_id')->index();
            $t->longText('result_text')->nullable(); $t->json('result_data')->nullable();
            $t->boolean('critical')->default(false)->index(); $t->string('status',30)->default('draft')->index();
            $t->timestamp('released_at')->nullable(); $t->string('released_by',80)->nullable(); $t->timestamps();
        });

        Schema::create('surgeries', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->index();
            $t->ulid('facility_id')->index(); $t->ulid('admission_id')->nullable()->index();
            $t->string('procedure_name',220); $t->string('room',80)->nullable();
            $t->string('status',30)->default('scheduled')->index(); $t->timestamp('scheduled_at')->index();
            $t->timestamp('started_at')->nullable(); $t->timestamp('ended_at')->nullable();
            $t->json('team')->nullable(); $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('sterilization_cycles', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('facility_id')->index();
            $t->string('cycle_code',60)->unique(); $t->string('equipment',120)->nullable();
            $t->string('status',30)->default('planned')->index(); $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable(); $t->json('indicators')->nullable();
            $t->string('released_by',80)->nullable(); $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->index();
            $t->ulid('encounter_id')->nullable()->index(); $t->ulid('admission_id')->nullable()->index();
            $t->string('payer_type',40)->default('private')->index(); $t->string('payer_name',180)->nullable();
            $t->string('status',30)->default('open')->index(); $t->decimal('gross_amount',14,2)->default(0);
            $t->decimal('discount_amount',14,2)->default(0); $t->decimal('net_amount',14,2)->default(0);
            $t->timestamp('closed_at')->nullable(); $t->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('invoice_id')->index();
            $t->string('code',80)->nullable()->index(); $t->string('description',220);
            $t->decimal('quantity',14,3)->default(1); $t->decimal('unit_price',14,2)->default(0);
            $t->decimal('total_price',14,2)->default(0); $t->string('source_type',80)->nullable();
            $t->string('source_id',80)->nullable()->index(); $t->timestamps();
        });

        Schema::create('documents', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('patient_id')->nullable()->index();
            $t->ulid('encounter_id')->nullable()->index(); $t->string('category',60)->index();
            $t->string('title',220); $t->string('storage_disk',40)->default('local');
            $t->string('storage_path',500); $t->string('mime_type',120)->nullable();
            $t->unsignedBigInteger('size_bytes')->nullable(); $t->string('checksum',128)->nullable()->index();
            $t->string('status',30)->default('active')->index(); $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->string('actor_id',80)->nullable()->index();
            $t->string('event',120)->index(); $t->string('entity_type',120)->nullable()->index();
            $t->string('entity_id',80)->nullable()->index(); $t->string('ip_address',64)->nullable();
            $t->string('user_agent',500)->nullable(); $t->json('context')->nullable();
            $t->timestamp('occurred_at')->index(); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs'); Schema::dropIfExists('documents');
        Schema::dropIfExists('invoice_items'); Schema::dropIfExists('invoices');
        Schema::dropIfExists('sterilization_cycles'); Schema::dropIfExists('surgeries');
        Schema::dropIfExists('exam_results'); Schema::dropIfExists('exam_orders');
        Schema::dropIfExists('nursing_records'); Schema::dropIfExists('bed_movements');
        Schema::dropIfExists('admissions'); Schema::dropIfExists('beds');
        Schema::dropIfExists('queue_tickets'); Schema::dropIfExists('appointments');
    }
};
