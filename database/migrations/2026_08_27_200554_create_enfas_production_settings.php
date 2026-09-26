<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('group_name', 80)->default('general');
                $table->string('key_name', 160);
                $table->longText('value')->nullable();
                $table->string('value_type', 30)->default('string');
                $table->timestamps();
                $table->unique(['group_name', 'key_name']);
            });
        }

        if (! Schema::hasTable('meta_integrations')) {
            Schema::create('meta_integrations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->default('WhatsApp principal');
                $table->string('waba_id', 120)->nullable();
                $table->string('phone_number_id', 120)->nullable();
                $table->string('business_id', 120)->nullable();
                $table->string('graph_version', 20)->default('v25.0');
                $table->longText('access_token')->nullable();
                $table->longText('app_secret')->nullable();
                $table->longText('verify_token')->nullable();
                $table->string('display_phone_number', 60)->nullable();
                $table->string('verified_name', 160)->nullable();
                $table->string('quality_rating', 60)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_tested_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Migração aditiva: não removemos dados automaticamente.
    }
};
