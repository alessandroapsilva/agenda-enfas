<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'microsoft_oid')) {
                $table->string('microsoft_oid', 191)->nullable()->unique()->after('email');
            }
            if (! Schema::hasColumn('users', 'microsoft_tenant_id')) {
                $table->string('microsoft_tenant_id', 191)->nullable()->after('microsoft_oid');
            }
            if (! Schema::hasColumn('users', 'microsoft_linked_at')) {
                $table->timestamp('microsoft_linked_at')->nullable()->after('microsoft_tenant_id');
            }
            if (! Schema::hasColumn('users', 'last_login_provider')) {
                $table->string('last_login_provider', 30)->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        // Migração aditiva de produção.
    }
};
