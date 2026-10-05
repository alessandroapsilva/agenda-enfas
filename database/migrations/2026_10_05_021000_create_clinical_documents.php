<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('document_type', 60)->index();
            $table->string('title', 180);
            $table->longText('body_template');
            $table->boolean('requires_signature')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('clinical_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('clinical_document_templates')->nullOnDelete();
            $table->string('document_type', 60)->index();
            $table->string('title', 180);
            $table->longText('content');
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
            $table->index(['patient_id','document_type']);
        });

        Schema::create('clinical_document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_document_id')->constrained('clinical_documents')->cascadeOnDelete();
            $table->string('signature_type', 40)->default('electronic');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name', 180);
            $table->string('signer_registry', 100)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->char('document_hash', 64);
            $table->string('provider', 60)->nullable();
            $table->string('provider_signature_id', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('signed_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_document_signatures');
        Schema::dropIfExists('clinical_documents');
        Schema::dropIfExists('clinical_document_templates');
    }
};
