<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data'=>[
            'patients'=>DB::table('patients')->count(),
            'appointments_today'=>DB::table('appointments')->whereDate('starts_at',today())->count(),
            'waiting'=>DB::table('queue_tickets')->where('status','waiting')->count(),
            'active_admissions'=>DB::table('admissions')->where('status','active')->count(),
            'open_prescriptions'=>DB::table('prescriptions')->whereIn('status',['draft','signed','active'])->count(),
            'low_stock_items'=>DB::table('medications')
                ->leftJoin('stock_lots','medications.id','=','stock_lots.medication_id')
                ->select('medications.id','medications.minimum_stock')
                ->groupBy('medications.id','medications.minimum_stock')
                ->havingRaw('COALESCE(SUM(stock_lots.quantity),0) <= medications.minimum_stock')
                ->get()->count(),
        ]]);
    }
}
