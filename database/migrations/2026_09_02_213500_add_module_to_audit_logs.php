<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('audit_logs')
            && ! Schema::hasColumn('audit_logs', 'module')
        ) {
            Schema::table('audit_logs', function (Blueprint $table): void {
                $table
                    ->string('module', 80)
                    ->nullable();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('audit_logs')
            && Schema::hasColumn('audit_logs', 'module')
        ) {
            Schema::table('audit_logs', function (Blueprint $table): void {
                $table->dropColumn('module');
            });
        }
    }
};
