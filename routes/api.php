<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DispensationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PharmacyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'service'=>config('sigh.name'),'status'=>'ok',
        'domain'=>config('sigh.domain'),'time'=>now()->toIso8601String(),
    ]));

    Route::get('/modules', fn () => response()->json([
        'data'=>collect(config('sigh.modules'))
            ->map(fn (array $module,string $key)=>['key'=>$key]+$module)->values(),
    ]));

    Route::get('/dashboard', DashboardController::class);
    Route::apiResource('patients',PatientController::class)->only(['index','store','show']);

    Route::prefix('far')->group(function (): void {
        Route::get('/dashboard',[PharmacyController::class,'dashboard']);
        Route::get('/stock',[PharmacyController::class,'stock']);
        Route::post('/dispensations',[DispensationController::class,'store']);
    });
});
