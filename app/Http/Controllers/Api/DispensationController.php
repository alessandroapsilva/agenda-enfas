<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pharmacy\DispensationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispensationController extends Controller
{
    public function store(Request $request, DispensationService $service): JsonResponse
    {
        $data=$request->validate([
            'pharmacy_id'=>['required','string','max:26'],
            'patient_id'=>['required','string','max:26'],
            'prescription_id'=>['nullable','string','max:26'],
            'items'=>['required','array','min:1'],
            'items.*.medication_id'=>['required','string','max:26'],
            'items.*.prescription_item_id'=>['nullable','string','max:26'],
            'items.*.quantity'=>['required','numeric','gt:0'],
        ]);

        $dispensation=$service->dispense($data,(string)optional($request->user())->id ?: null);

        return response()->json(['data'=>$dispensation],201);
    }
}
