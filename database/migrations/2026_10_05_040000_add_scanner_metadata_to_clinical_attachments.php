<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinical_attachments') && ! Schema::hasColumn('clinical_attachments', 'scan_metadata')) {
            Schema::table('clinical_attachments', function (Blueprint $table) {
                $table->json('scan_metadata')->nullable()->after('source');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clinical_attachments') && Schema::hasColumn('clinical_attachments', 'scan_metadata')) {
            Schema::table('clinical_attachments', function (Blueprint $table) {
                $table->dropColumn('scan_metadata');
            });
        }
    }
};
