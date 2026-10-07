<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('confirmation_attempts')) {
            return;
        }

        Schema::create('confirmation_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->index();
            $table->string('channel', 32)->index();
            $table->string('status', 32)->default('queued')->index();
            $table->string('outcome', 32)->nullable()->index();
            $table->string('reason', 64)->nullable();
            $table->string('source', 64)->default('automation');
            $table->string('provider', 64)->nullable();
            $table->string('recipient', 40)->nullable();
            $table->unsignedSmallInteger('attempt_no')->default(1);
            $table->string('dedupe_key', 191)->unique();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('external_id', 191)->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['appointment_id', 'channel', 'status'],
                'confirmation_attempts_queue_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmation_attempts');
    }
};
