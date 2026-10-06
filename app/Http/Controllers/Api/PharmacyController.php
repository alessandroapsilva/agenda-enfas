<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\StockLot;
use Illuminate\Http\JsonResponse;

class PharmacyController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $nearExpiry = StockLot::query()
            ->with('medication:id,name,generic_name')
            ->where('quantity','>',0)
            ->whereDate('expires_at','<=',now()->addDays(90))
            ->orderBy('expires_at')->limit(20)->get();

        $lowStock = Medication::query()
            ->withSum(['lots as available_stock' => fn ($q) => $q->where('status','available')], 'quantity')
            ->where('is_active',true)->get()
            ->filter(fn (Medication $m) => (float)($m->available_stock ?? 0) <= (float)$m->minimum_stock)
            ->values()->take(20);

        return response()->json(['data'=>[
            'near_expiry'=>$nearExpiry,
            'low_stock'=>$lowStock,
        ]]);
    }

    public function stock(): JsonResponse
    {
        return response()->json(
            Medication::query()
                ->withSum(['lots as stock' => fn ($q) => $q->where('status','available')], 'quantity')
                ->orderBy('name')->paginate(50)
        );
    }
}
