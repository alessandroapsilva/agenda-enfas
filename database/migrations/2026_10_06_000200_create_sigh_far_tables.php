<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('organization_id')->index();
            $t->ulid('facility_id')->nullable()->index(); $t->string('name',180);
            $t->string('code',40)->nullable(); $t->string('type',40)->default('central');
            $t->boolean('active')->default(true); $t->timestamps();
        });

        Schema::create('medications', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('organization_id')->index();
            $t->string('code',60)->nullable()->index(); $t->string('name',220)->index();
            $t->string('generic_name',220)->nullable()->index(); $t->string('presentation',220)->nullable();
            $t->string('pharmaceutical_form',100)->nullable(); $t->string('concentration',100)->nullable();
            $t->string('unit',30)->nullable(); $t->boolean('is_controlled')->default(false);
            $t->boolean('is_active')->default(true); $t->decimal('minimum_stock',14,3)->default(0);
            $t->json('metadata')->nullable(); $t->timestamps();
        });

        Schema::create('stock_lots', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('pharmacy_id')->index(); $t->ulid('medication_id')->index();
            $t->string('batch',100)->index(); $t->date('expires_at')->nullable()->index();
            $t->decimal('quantity',14,3)->default(0); $t->decimal('reserved_quantity',14,3)->default(0);
            $t->decimal('unit_cost',14,4)->nullable(); $t->string('status',30)->default('available')->index();
            $t->timestamps(); $t->unique(['pharmacy_id','medication_id','batch']);
        });

        Schema::create('inventory_movements', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('pharmacy_id')->index(); $t->ulid('medication_id')->index();
            $t->ulid('stock_lot_id')->nullable()->index(); $t->string('type',40)->index();
            $t->decimal('quantity',14,3); $t->string('reference_type',80)->nullable();
            $t->string('reference_id',80)->nullable()->index(); $t->string('performed_by',80)->nullable()->index();
            $t->timestamp('occurred_at')->index(); $t->text('notes')->nullable(); $t->timestamps();
        });

        Schema::create('dispensations', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('pharmacy_id')->index(); $t->ulid('patient_id')->index();
            $t->ulid('prescription_id')->nullable()->index(); $t->string('status',30)->default('pending')->index();
            $t->string('dispensed_by',80)->nullable(); $t->timestamp('dispensed_at')->nullable();
            $t->text('notes')->nullable(); $t->timestamps();
        });

        Schema::create('dispensation_items', function (Blueprint $t): void {
            $t->ulid('id')->primary(); $t->ulid('dispensation_id')->index();
            $t->ulid('prescription_item_id')->nullable()->index(); $t->ulid('medication_id')->index();
            $t->ulid('stock_lot_id')->nullable()->index(); $t->decimal('quantity',14,3); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensation_items'); Schema::dropIfExists('dispensations');
        Schema::dropIfExists('inventory_movements'); Schema::dropIfExists('stock_lots');
        Schema::dropIfExists('medications'); Schema::dropIfExists('pharmacies');
    }
};
