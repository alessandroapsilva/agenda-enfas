<?php

namespace App\Http\Controllers;

use App\Services\Enfas\CepLookupService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class CepLookupController extends Controller
{
    public function show(string $cep, CepLookupService $lookup): JsonResponse
    {
        try {
            return response()->json([
                'ok' => true,
                'data' => $lookup->lookup($cep),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
