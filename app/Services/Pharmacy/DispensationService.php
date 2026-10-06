<?php

namespace App\Services\Pharmacy;

use App\Models\Dispensation;
use App\Models\DispensationItem;
use App\Models\StockLot;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DispensationService
{
    public function __construct(private InventoryService $inventory) {}

    public function dispense(array $data, ?string $actorId=null): Dispensation
    {
        return DB::transaction(function () use ($data,$actorId): Dispensation {
            $dispensation=Dispensation::create([
                'pharmacy_id'=>$data['pharmacy_id'],
                'patient_id'=>$data['patient_id'],
                'prescription_id'=>$data['prescription_id'] ?? null,
                'status'=>'processing',
                'dispensed_by'=>$actorId,
            ]);

            foreach ($data['items'] as $requested) {
                $remaining=(float)$requested['quantity'];

                $lots=StockLot::query()
                    ->where('pharmacy_id',$data['pharmacy_id'])
                    ->where('medication_id',$requested['medication_id'])
                    ->where('status','available')
                    ->where('quantity','>',0)
                    ->where(function ($q): void {
                        $q->whereNull('expires_at')->orWhereDate('expires_at','>=',now()->toDateString());
                    })
                    ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('expires_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($lots as $lot) {
                    if ($remaining <= 0) break;

                    $available=max(0,(float)$lot->quantity-(float)$lot->reserved_quantity);
                    if ($available <= 0) continue;

                    $take=min($available,$remaining);

                    $this->inventory->consumeLot(
                        $lot->id,$take,'dispensation',$actorId,
                        Dispensation::class,$dispensation->id
                    );

                    DispensationItem::create([
                        'dispensation_id'=>$dispensation->id,
                        'prescription_item_id'=>$requested['prescription_item_id'] ?? null,
                        'medication_id'=>$requested['medication_id'],
                        'stock_lot_id'=>$lot->id,
                        'quantity'=>$take,
                    ]);

                    $remaining-=$take;
                }

                if ($remaining > 0.0001) {
                    throw new RuntimeException('Estoque insuficiente para concluir a dispensação.');
                }
            }

            $dispensation->update([
                'status'=>'completed',
                'dispensed_at'=>now(),
            ]);

            return $dispensation->load('items');
        });
    }
}
