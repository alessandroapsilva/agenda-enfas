<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('professional_blocks')) {
            Schema::create('professional_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('type', 30)->default('block');
                $table->string('title', 160)->nullable();
                $table->text('reason')->nullable();
                $table->boolean('is_all_day')->default(false);
                $table->timestamps();

                $table->index(['professional_id', 'starts_at', 'ends_at'], 'professional_block_lookup');
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
