<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_attachments', function (Blueprint $table) {
            $table->string('ocr_status', 30)->default('not_requested')->index()->after('ocr_text');
            $table->text('ocr_error')->nullable()->after('ocr_status');
            $table->timestamp('ocr_processed_at')->nullable()->index()->after('ocr_error');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_attachments', function (Blueprint $table) {
            $table->dropColumn(['ocr_status', 'ocr_error', 'ocr_processed_at']);
        });
    }
};
