<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['professionals','patients','services'] as $table) {
            if (Schema::hasTable($table)
                && ! Schema::hasColumn($table,'is_active')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->boolean('is_active')->default(true)->index();
                });
            }
        }

        if (Schema::hasTable('professionals')) {
            $this->add('professionals','email',fn(Blueprint $t)=>$t->string('email')->nullable());
            $this->add('professionals','phone',fn(Blueprint $t)=>$t->string('phone',40)->nullable());
            $this->add('professionals','specialty',fn(Blueprint $t)=>$t->string('specialty')->nullable());
            $this->add('professionals','registration_type',fn(Blueprint $t)=>$t->string('registration_type',30)->nullable());
            $this->add('professionals','registration_number',fn(Blueprint $t)=>$t->string('registration_number',60)->nullable());
            $this->add('professionals','notes',fn(Blueprint $t)=>$t->text('notes')->nullable());
        }

        if (Schema::hasTable('patients')) {
            $this->add('patients','email',fn(Blueprint $t)=>$t->string('email')->nullable());
            $this->add('patients','cpf',fn(Blueprint $t)=>$t->string('cpf',20)->nullable()->index());
            $this->add('patients','birth_date',fn(Blueprint $t)=>$t->date('birth_date')->nullable());
            $this->add('patients','notes',fn(Blueprint $t)=>$t->text('notes')->nullable());
        }

        if (Schema::hasTable('services')) {
            $this->add('services','description',fn(Blueprint $t)=>$t->text('description')->nullable());
            $this->add('services','duration_minutes',fn(Blueprint $t)=>$t->unsignedInteger('duration_minutes')->default(30));
            $this->add('services','price',fn(Blueprint $t)=>$t->decimal('price',10,2)->nullable());
            $this->add('services','preparation',fn(Blueprint $t)=>$t->text('preparation')->nullable());
        }

        if (! Schema::hasTable('locations')) {
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

        if (Schema::hasTable('appointments')
            && ! Schema::hasColumn('appointments','location_id')) {
            Schema::table('appointments', function (Blueprint $t) {
                $t->unsignedBigInteger('location_id')->nullable()->index();
            });
        }

        if (! Schema::hasTable('media_assets')) {
            Schema::create('media_assets', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('kind',20)->default('image');
                $t->string('disk',30)->default('public');
                $t->string('path');
                $t->string('mime_type',100);
                $t->unsignedBigInteger('size')->default(0);
                $t->longText('meta_handle')->nullable();
                $t->timestamp('meta_handle_at')->nullable();
                $t->boolean('is_active')->default(true)->index();
                $t->unsignedBigInteger('uploaded_by')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }

        if (Schema::hasTable('wa_templates')) {
            $this->add('wa_templates','is_active',fn(Blueprint $t)=>$t->boolean('is_active')->default(true)->index());
            $this->add('wa_templates','header_type',fn(Blueprint $t)=>$t->string('header_type',20)->default('TEXT'));
            $this->add('wa_templates','header_media_id',fn(Blueprint $t)=>$t->unsignedBigInteger('header_media_id')->nullable()->index());
            $this->add('wa_templates','archived_at',fn(Blueprint $t)=>$t->timestamp('archived_at')->nullable()->index());
            $this->add('wa_templates','updated_by',fn(Blueprint $t)=>$t->unsignedBigInteger('updated_by')->nullable());
            $this->add('wa_templates','version',fn(Blueprint $t)=>$t->unsignedInteger('version')->default(1));
            $this->add('wa_templates','last_error',fn(Blueprint $t)=>$t->text('last_error')->nullable());
        }
    }

    private function add(string $table,string $column,callable $fn): void
    {
        if (! Schema::hasColumn($table,$column)) {
            Schema::table($table, function (Blueprint $t) use ($fn) {
                $fn($t);
            });
        }
    }

    public function down(): void
    {
        // Rollback destrutivo propositalmente evitado.
    }
};
