<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()
            ->orderBy('name')
            ->get();

        return view(
            'services.index',
            compact('services')
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:60', 'unique:services,code'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'description' => ['nullable', 'string', 'max:5000'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        Service::create([
            'name' => trim($data['name']),
            'code' => $data['code']
                ? Str::upper(trim($data['code']))
                : null,

            'duration_minutes' => $data['duration_minutes'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'],
            'is_active' => true,
        ]);

        return back()->with(
            'success',
            'Serviço cadastrado com sucesso.'
        );
    }
}
