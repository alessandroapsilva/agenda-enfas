<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Patient::query()->latest();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('medical_record_number', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('cns', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['required','string','max:26'],
            'medical_record_number' => ['required','string','max:40'],
            'name' => ['required','string','max:180'],
            'social_name' => ['nullable','string','max:180'],
            'cpf' => ['nullable','string','max:14'],
            'cns' => ['nullable','string','max:20'],
            'birth_date' => ['nullable','date'],
            'sex' => ['nullable','string','max:30'],
            'phone' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:180'],
            'mother_name' => ['nullable','string','max:180'],
            'metadata' => ['nullable','array'],
        ]);

        return response()->json(['data' => Patient::create($data)], 201);
    }

    public function show(Patient $patient): JsonResponse
    {
        return response()->json([
            'data' => $patient->load([
                'encounters' => fn ($query) => $query->latest()->limit(20),
                'prescriptions' => fn ($query) => $query->latest()->limit(20),
            ]),
        ]);
    }
}
