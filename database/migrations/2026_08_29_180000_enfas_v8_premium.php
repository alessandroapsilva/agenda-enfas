<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('phone',40)->nullable();
                $t->string('email')->nullable();
                $t->string('address')->nullable();
                $t->string('city')->nullable();
                $t->string('state',2)->nullable();
                $t->string('postal_code',20)->nullable();
                $t->text('notes')->nullable();
                $t->boolean('is_active')->default(true)->index();
                $t->timestamps();
            });
        }

        $this->add('professionals','email',fn($t)=>$t->string('email')->nullable());
        $this->add('professionals','phone',fn($t)=>$t->string('phone',40)->nullable());
        $this->add('professionals','specialty',fn($t)=>$t->string('specialty')->nullable());
        $this->add('professionals','registration_type',fn($t)=>$t->string('registration_type',30)->nullable());
        $this->add('professionals','registration_number',fn($t)=>$t->string('registration_number',60)->nullable());
        $this->add('professionals','photo_path',fn($t)=>$t->string('photo_path')->nullable());
        $this->add('professionals','color',fn($t)=>$t->string('color',20)->nullable());
        $this->add('professionals','notes',fn($t)=>$t->text('notes')->nullable());
        $this->add('professionals','is_active',fn($t)=>$t->boolean('is_active')->default(true)->index());

        $this->add('patients','email',fn($t)=>$t->string('email')->nullable());
        $this->add('patients','cpf',fn($t)=>$t->string('cpf',20)->nullable()->index());
        $this->add('patients','birth_date',fn($t)=>$t->date('birth_date')->nullable());
        $this->add('patients','notes',fn($t)=>$t->text('notes')->nullable());
        $this->add('patients','whatsapp_opt_in',fn($t)=>$t->boolean('whatsapp_opt_in')->default(true));
        $this->add('patients','is_active',fn($t)=>$t->boolean('is_active')->default(true)->index());

        $this->add('services','description',fn($t)=>$t->text('description')->nullable());
        $this->add('services','duration_minutes',fn($t)=>$t->unsignedInteger('duration_minutes')->default(30));
        $this->add('services','price',fn($t)=>$t->decimal('price',10,2)->nullable());
        $this->add('services','preparation',fn($t)=>$t->text('preparation')->nullable());
        $this->add('services','color',fn($t)=>$t->string('color',20)->nullable());
        $this->add('services','is_active',fn($t)=>$t->boolean('is_active')->default(true)->index());

        $this->add('appointments','location_id',fn($t)=>$t->unsignedBigInteger('location_id')->nullable()->index());
        $this->add('appointments','notes',fn($t)=>$t->text('notes')->nullable());
        $this->add('appointments','source',fn($t)=>$t->string('source',30)->default('manual')->index());
        $this->add('appointments','confirmed_at',fn($t)=>$t->timestamp('confirmed_at')->nullable());
        $this->add('appointments','cancelled_at',fn($t)=>$t->timestamp('cancelled_at')->nullable());
        $this->add('appointments','attended_at',fn($t)=>$t->timestamp('attended_at')->nullable());
        $this->add('appointments','no_show_at',fn($t)=>$t->timestamp('no_show_at')->nullable());

        if (!Schema::hasTable('professional_availabilities')) {
            Schema::create('professional_availabilities',function(Blueprint $t){
                $t->id(); $t->unsignedBigInteger('professional_id')->index();
                $t->unsignedBigInteger('location_id')->nullable()->index();
                $t->unsignedTinyInteger('weekday')->index();
                $t->time('starts_at'); $t->time('ends_at');
                $t->unsignedInteger('slot_minutes')->default(30);
                $t->boolean('is_active')->default(true)->index(); $t->timestamps();
            });
        }

        if (!Schema::hasTable('professional_absences')) {
            Schema::create('professional_absences',function(Blueprint $t){
                $t->id(); $t->unsignedBigInteger('professional_id')->index();
                $t->dateTime('starts_at')->index(); $t->dateTime('ends_at')->index();
                $t->string('reason')->nullable(); $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('media_assets')) {
            Schema::create('media_assets',function(Blueprint $t){
                $t->id(); $t->string('name'); $t->string('kind',20)->default('image');
                $t->string('disk',30)->default('public'); $t->string('path');
                $t->string('mime_type',100); $t->unsignedBigInteger('size')->default(0);
                $t->longText('meta_handle')->nullable(); $t->timestamp('meta_handle_at')->nullable();
                $t->boolean('is_active')->default(true)->index();
                $t->unsignedBigInteger('uploaded_by')->nullable(); $t->timestamps(); $t->softDeletes();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs',function(Blueprint $t){
                $t->id(); $t->unsignedBigInteger('user_id')->nullable()->index();
                $t->string('module',60)->index(); $t->string('action',60)->index();
                $t->string('entity_type')->nullable(); $t->unsignedBigInteger('entity_id')->nullable();
                $t->string('description')->nullable(); $t->json('old_values')->nullable();
                $t->json('new_values')->nullable(); $t->string('ip',64)->nullable();
                $t->text('user_agent')->nullable(); $t->timestamps();
            });
        }

        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings',function(Blueprint $t){
                $t->id(); $t->string('group',60)->default('general');
                $t->string('key')->unique(); $t->longText('value')->nullable();
                $t->string('type',30)->default('string'); $t->timestamps();
            });
        }

        if (!Schema::hasTable('v8_automation_runs')) {
            Schema::create('v8_automation_runs',function(Blueprint $t){
                $t->id(); $t->unsignedBigInteger('automation_id')->index();
                $t->unsignedBigInteger('appointment_id')->index();
                $t->unsignedBigInteger('template_id')->nullable()->index();
                $t->string('run_key')->unique(); $t->string('status',30)->index();
                $t->text('error')->nullable(); $t->timestamp('sent_at')->nullable(); $t->timestamps();
            });
        }

        if (Schema::hasTable('wa_automations')) {
            $this->add('wa_automations','trigger_type',fn($t)=>$t->string('trigger_type',30)->default('before')->index());
            $this->add('wa_automations','offset_minutes',fn($t)=>$t->integer('offset_minutes')->default(1440));
            $this->add('wa_automations','wa_template_id',fn($t)=>$t->unsignedBigInteger('wa_template_id')->nullable()->index());
            $this->add('wa_automations','is_active',fn($t)=>$t->boolean('is_active')->default(false)->index());
            $this->add('wa_automations','last_run_at',fn($t)=>$t->timestamp('last_run_at')->nullable());
        }

        if (Schema::hasTable('wa_templates')) {
            $this->add('wa_templates','is_active',fn($t)=>$t->boolean('is_active')->default(true)->index());
            $this->add('wa_templates','header_type',fn($t)=>$t->string('header_type',20)->default('TEXT')->index());
            $this->add('wa_templates','header_media_id',fn($t)=>$t->unsignedBigInteger('header_media_id')->nullable()->index());
            $this->add('wa_templates','archived_at',fn($t)=>$t->timestamp('archived_at')->nullable()->index());
            $this->add('wa_templates','updated_by',fn($t)=>$t->unsignedBigInteger('updated_by')->nullable());
            $this->add('wa_templates','version',fn($t)=>$t->unsignedInteger('version')->default(1));
            $this->add('wa_templates','last_error',fn($t)=>$t->text('last_error')->nullable());
        }

        foreach (['professionals','patients','services'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table,'active') && Schema::hasColumn($table,'is_active')) {
                DB::table($table)->update(['is_active'=>DB::raw('COALESCE(active,1)')]);
            }
        }
    }

    private function add(string $table,string $column,callable $fn): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table,$column)) {
            Schema::table($table,function(Blueprint $t) use($fn){$fn($t);});
        }
    }

    public function down(): void {}
};
