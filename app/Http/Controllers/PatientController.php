<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q'));

        $patients = Patient::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view(
            'patients.index',
            compact('patients', 'search')
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'cpf' => ['nullable', 'string', 'max:20', 'unique:patients,cpf'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['name'] = trim($data['name']);
        $data['phone'] = trim($data['phone']);
        $data['is_active'] = true;

        Patient::create($data);

        return back()->with(
            'success',
            'Paciente cadastrado com sucesso.'
        );
    }
}
