<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            if (! Schema::hasColumn('professionals', 'council_type')) {
                $table->string('council_type', 30)->nullable()->after('specialty');
            }
            if (! Schema::hasColumn('professionals', 'council_number')) {
                $table->string('council_number', 60)->nullable()->after('council_type');
            }
            if (! Schema::hasColumn('professionals', 'council_state')) {
                $table->string('council_state', 2)->nullable()->after('council_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            foreach (['council_state','council_number','council_type'] as $column) {
                if (Schema::hasColumn('professionals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
