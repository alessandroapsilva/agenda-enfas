<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_clinical_evolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('professional_id')->constrained()->restrictOnDelete();
            $table->string('format', 30)->default('soap')->index();
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->longText('body')->nullable();
            $table->char('integrity_hash', 64)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->index();
            $table->timestamps();

            $table->index(['appointment_id', 'signed_at'], 'evolution_appointment_date_idx');
            $table->index(['patient_id', 'signed_at'], 'evolution_patient_date_idx');
        });

        Schema::create('clinical_protocol_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('clinical_protocol_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('clinical_protocol_templates')->cascadeOnDelete();
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'sort_order'], 'protocol_item_order_idx');
        });

        Schema::create('appointment_protocol_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('professional_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained('clinical_protocol_templates')->restrictOnDelete();
            $table->string('status', 30)->default('in_progress')->index();
            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['appointment_id', 'status'], 'protocol_run_appointment_status_idx');
        });

        Schema::create('appointment_protocol_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_run_id')->constrained('appointment_protocol_runs')->cascadeOnDelete();
            $table->foreignId('template_item_id')->constrained('clinical_protocol_template_items')->restrictOnDelete();
            $table->string('value', 30);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['protocol_run_id', 'template_item_id'], 'protocol_response_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_protocol_responses');
        Schema::dropIfExists('appointment_protocol_runs');
        Schema::dropIfExists('clinical_protocol_template_items');
        Schema::dropIfExists('clinical_protocol_templates');
        Schema::dropIfExists('appointment_clinical_evolutions');
    }
};
