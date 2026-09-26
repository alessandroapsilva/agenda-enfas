<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'preferred_name')) {
                $table->string('preferred_name', 120)->nullable()->after('name');
            }
            if (! Schema::hasColumn('patients', 'secondary_phone')) {
                $table->string('secondary_phone', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('patients', 'preferred_contact_channel')) {
                $table->string('preferred_contact_channel', 30)->default('whatsapp')->after('email');
            }
            if (! Schema::hasColumn('patients', 'contact_consent')) {
                $table->boolean('contact_consent')->default(true)->after('preferred_contact_channel');
            }
            if (! Schema::hasColumn('patients', 'contact_consent_at')) {
                $table->timestamp('contact_consent_at')->nullable()->after('contact_consent');
            }
            if (! Schema::hasColumn('patients', 'contact_consent_source')) {
                $table->string('contact_consent_source', 60)->nullable()->after('contact_consent_at');
            }
            if (! Schema::hasColumn('patients', 'do_not_contact')) {
                $table->boolean('do_not_contact')->default(false)->after('contact_consent_source');
            }
            if (! Schema::hasColumn('patients', 'address_line')) {
                $table->string('address_line', 255)->nullable()->after('birth_date');
            }
            if (! Schema::hasColumn('patients', 'address_number')) {
                $table->string('address_number', 30)->nullable()->after('address_line');
            }
            if (! Schema::hasColumn('patients', 'address_complement')) {
                $table->string('address_complement', 120)->nullable()->after('address_number');
            }
            if (! Schema::hasColumn('patients', 'neighborhood')) {
                $table->string('neighborhood', 120)->nullable()->after('address_complement');
            }
            if (! Schema::hasColumn('patients', 'city')) {
                $table->string('city', 120)->nullable()->after('neighborhood');
            }
            if (! Schema::hasColumn('patients', 'state')) {
                $table->string('state', 2)->nullable()->after('city');
            }
            if (! Schema::hasColumn('patients', 'postal_code')) {
                $table->string('postal_code', 12)->nullable()->after('state');
            }
            if (! Schema::hasColumn('patients', 'tags')) {
                $table->json('tags')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('patients', 'last_contact_at')) {
                $table->timestamp('last_contact_at')->nullable()->after('tags');
            }
        });
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
