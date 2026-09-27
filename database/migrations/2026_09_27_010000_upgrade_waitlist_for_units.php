<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('waitlist_entries')
            && ! Schema::hasColumn('waitlist_entries', 'location_id')
        ) {
            Schema::table('waitlist_entries', function (Blueprint $table) {
                $table->foreignId('location_id')
                    ->nullable()
                    ->after('professional_id')
                    ->constrained('locations')
                    ->nullOnDelete();

                $table->index(
                    ['service_id','location_id','status'],
                    'waitlist_location_lookup'
                );
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
