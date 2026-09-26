<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('professional_availabilities')) {
            Schema::create('professional_availabilities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('professional_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedTinyInteger('weekday')->index();
                $table->time('starts_at');
                $table->time('ends_at');
                $table->unsignedInteger('slot_minutes')->default(30);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('professional_absences')) {
            Schema::create('professional_absences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('professional_id')->index();
                $table->dateTime('starts_at')->index();
                $table->dateTime('ends_at')->index();
                $table->string('reason')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('module',60)->index();
                $table->string('action',60)->index();
                $table->string('entity_type')->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->string('description')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip',64)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('enfas_settings')) {
            Schema::create('enfas_settings', function (Blueprint $table) {
                $table->id();
                $table->string('group',60)->default('general')->index();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type',30)->default('string');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('operational_alerts')) {
            Schema::create('operational_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('severity',20)->default('info')->index();
                $table->string('source',80)->default('system')->index();
                $table->string('title');
                $table->text('message')->nullable();
                $table->json('context')->nullable();
                $table->string('status',20)->default('open')->index();
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('appointments')) {
            if (! Schema::hasColumn('appointments','location_id')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->unsignedBigInteger('location_id')->nullable()->index();
                });
            }

            foreach([
                'confirmed_at',
                'cancelled_at',
                'attended_at',
                'no_show_at',
            ] as $column) {
                if (! Schema::hasColumn('appointments',$column)) {
                    Schema::table('appointments', function (Blueprint $table) use ($column) {
                        $table->timestamp($column)->nullable()->index();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        // Conservador: produção não remove dados operacionais automaticamente.
    }
};
