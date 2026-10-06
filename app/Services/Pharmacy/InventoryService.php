<?php

namespace App\Services\Pharmacy;

use App\Models\StockLot;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function consumeLot(
        string $stockLotId,
        float $quantity,
        string $movementType,
        ?string $performedBy=null,
        ?string $referenceType=null,
        ?string $referenceId=null,
    ): StockLot {
        if ($quantity <= 0) {
            throw new RuntimeException('A quantidade deve ser maior que zero.');
        }

        return DB::transaction(function () use (
            $stockLotId,$quantity,$movementType,$performedBy,$referenceType,$referenceId
        ): StockLot {
            $lot=StockLot::query()->lockForUpdate()->findOrFail($stockLotId);
            $available=(float)$lot->quantity-(float)$lot->reserved_quantity;

            if ($available < $quantity) {
                throw new RuntimeException('Saldo insuficiente no lote informado.');
            }

            if ($lot->expires_at && $lot->expires_at->isPast()) {
                throw new RuntimeException('Não é permitido dispensar lote vencido.');
            }

            $lot->quantity=(float)$lot->quantity-$quantity;
            $lot->save();

            DB::table('inventory_movements')->insert([
                'id'=>(string)str()->ulid(),
                'pharmacy_id'=>$lot->pharmacy_id,
                'medication_id'=>$lot->medication_id,
                'stock_lot_id'=>$lot->id,
                'type'=>$movementType,
                'quantity'=>-1*$quantity,
                'reference_type'=>$referenceType,
                'reference_id'=>$referenceId,
                'performed_by'=>$performedBy,
                'occurred_at'=>now(),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);

            return $lot->refresh();
        });
    }
}
