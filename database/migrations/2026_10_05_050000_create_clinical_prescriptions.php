<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('professional_id')->constrained()->restrictOnDelete();
            $table->foreignId('clinical_document_id')->nullable()->unique()->constrained('clinical_documents')->nullOnDelete();
            $table->string('prescription_type', 40)->default('simple')->index();
            $table->string('title', 180)->default('Prescrição');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamp('signed_at')->nullable()->index();
            $table->char('content_hash', 64)->nullable()->index();
            $table->string('external_provider', 60)->nullable();
            $table->string('external_id', 180)->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['professional_id', 'status']);
        });

        Schema::create('clinical_prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_prescription_id')->constrained('clinical_prescriptions')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('medication_name', 180);
            $table->string('concentration', 120)->nullable();
            $table->string('dosage_form', 120)->nullable();
            $table->string('route', 120)->nullable();
            $table->string('quantity', 120)->nullable();
            $table->text('directions');
            $table->string('duration', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinical_prescription_id', 'sort_order'], 'rx_items_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_prescription_items');
        Schema::dropIfExists('clinical_prescriptions');
    }
};
