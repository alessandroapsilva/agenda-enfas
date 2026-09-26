<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('waitlist_entries')) {
            Schema::create('waitlist_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
                $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
                $table->string('preferred_period', 20)->nullable();
                $table->date('earliest_date')->nullable();
                $table->date('latest_date')->nullable();
                $table->string('status', 30)->default('waiting');
                $table->dateTime('offered_start_at')->nullable();
                $table->dateTime('offered_end_at')->nullable();
                $table->timestamp('offer_expires_at')->nullable();
                $table->unsignedBigInteger('appointment_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['service_id', 'professional_id', 'status'], 'waitlist_match_lookup');
                $table->index(['status', 'offer_expires_at']);
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
