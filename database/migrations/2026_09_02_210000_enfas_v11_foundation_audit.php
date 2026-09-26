<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {

            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->nullable()
                    ->index();

                $table->string('module', 100)
                    ->nullable()
                    ->index();

                $table->string('action', 100)
                    ->nullable()
                    ->index();

                $table->text('description')
                    ->nullable();

                $table->string('ip', 45)
                    ->nullable();

                $table->text('user_agent')
                    ->nullable();

                $table->timestamps();
            });

            return;
        }

        $missing = [
            'module' =>
                ! Schema::hasColumn(
                    'audit_logs',
                    'module'
                ),

            'action' =>
                ! Schema::hasColumn(
                    'audit_logs',
                    'action'
                ),

            'description' =>
                ! Schema::hasColumn(
                    'audit_logs',
                    'description'
                ),

            'ip' =>
                ! Schema::hasColumn(
                    'audit_logs',
                    'ip'
                ),

            'user_agent' =>
                ! Schema::hasColumn(
                    'audit_logs',
                    'user_agent'
                ),

            'created_at' =>
                ! Schema::hasColumn(
                    'audit_logs',
                    'created_at'
                ),

            'updated_at' =>
                ! Schema::hasColumn(
                    'audit_logs',
                    'updated_at'
                ),
        ];

        Schema::table(
            'audit_logs',
            function (Blueprint $table) use ($missing) {

                if ($missing['module']) {
                    $table->string(
                        'module',
                        100
                    )->nullable()->index();
                }

                if ($missing['action']) {
                    $table->string(
                        'action',
                        100
                    )->nullable()->index();
                }

                if ($missing['description']) {
                    $table->text(
                        'description'
                    )->nullable();
                }

                if ($missing['ip']) {
                    $table->string(
                        'ip',
                        45
                    )->nullable();
                }

                if ($missing['user_agent']) {
                    $table->text(
                        'user_agent'
                    )->nullable();
                }

                if ($missing['created_at']) {
                    $table->timestamp(
                        'created_at'
                    )->nullable();
                }

                if ($missing['updated_at']) {
                    $table->timestamp(
                        'updated_at'
                    )->nullable();
                }
            }
        );
    }

    public function down(): void
    {
        /*
         * Migration deliberadamente não destrutiva.
         *
         * audit_logs já existia antes da V11.
         * Não removemos colunas de auditoria em rollback
         * para não perder histórico operacional.
         */
    }
};
