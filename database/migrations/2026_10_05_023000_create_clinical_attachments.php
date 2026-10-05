<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinical_record_id')->nullable()->constrained('appointment_clinical_records')->nullOnDelete();
            $table->foreignId('clinical_document_id')->nullable()->constrained('clinical_documents')->nullOnDelete();
            $table->string('category', 60)->default('other')->index();
            $table->string('title', 180);
            $table->string('original_name', 255);
            $table->string('disk', 30)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('sha256', 64)->index();
            $table->string('source', 30)->default('upload')->index();
            $table->longText('ocr_text')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['patient_id','appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_attachments');
    }
};
