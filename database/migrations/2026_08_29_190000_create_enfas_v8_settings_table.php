<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enfas_v8_settings')) {
            Schema::create('enfas_v8_settings', function (Blueprint $table) {
                $table->id();
                $table->string('group', 60)->default('general')->index();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type', 30)->default('string');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enfas_v8_settings');
    }
};
