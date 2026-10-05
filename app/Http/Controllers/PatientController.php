<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\Enfas\MetaWhatsAppService;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function index(Request $request, AccessScopeService $access)
    {
        $search = trim((string) $request->get('q'));

        $patients = $access->patients(Patient::query(), $request->user())
            ->withCount('appointments')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('preferred_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('secondary_phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('rgea_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function show(Request $request, Patient $patient, AccessScopeService $access)
    {
        abort_unless($access->canViewPatient($request->user(), $patient), 403);
        $patient->load([
            'appointments' => fn ($query) => $query
                ->with(['professional', 'service', 'clinicalRecord'])
                ->orderByDesc('start_at')
                ->limit(20),
            'messages' => fn ($query) => $query
                ->orderByDesc('id')
                ->limit(30),
        ]);

        return view('patients.show', compact('patient'));
    }

    public function store(Request $request)
    {
        if ($request->filled('rgea_number')) {
            $request->merge([
                'rgea_number' => strtoupper(trim((string) $request->input('rgea_number'))),
            ]);
        }

        $data = $this->validated($request);

        $data['name'] = trim($data['name']);
        $data['phone'] = trim($data['phone']);
        $data['rgea_number'] = filled($data['rgea_number'] ?? null)
            ? strtoupper(trim($data['rgea_number']))
            : null;
        $data['is_active'] = true;

        if (! empty($data['contact_consent'])) {
            $data['contact_consent_at'] = now();
            $data['contact_consent_source'] = 'internal_registration';
        }

        $patient = Patient::create($data);

        return back()->with(
            'success',
            'Paciente cadastrado com sucesso. RGEA: '.$patient->rgea_number.'.'
        );
    }

    public function update(Request $request, Patient $patient)
    {
        if ($request->filled('rgea_number')) {
            $request->merge([
                'rgea_number' => strtoupper(trim((string) $request->input('rgea_number'))),
            ]);
        }

        $data = $this->validated($request, $patient);

        $data['name'] = trim($data['name']);
        $data['phone'] = trim($data['phone']);
        $data['rgea_number'] = filled($data['rgea_number'] ?? null)
            ? strtoupper(trim($data['rgea_number']))
            : $patient->rgea_number;

        if (! empty($data['contact_consent']) && ! $patient->contact_consent_at) {
            $data['contact_consent_at'] = now();
            $data['contact_consent_source'] = 'internal_update';
        }

        if (empty($data['contact_consent'])) {
            $data['contact_consent_at'] = null;
            $data['contact_consent_source'] = null;
        }

        $patient->update($data);

        return back()->with('success', 'Cadastro do paciente atualizado.');
    }

    public function contact(
        Request $request,
        Patient $patient,
        MetaWhatsAppService $meta
    ) {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        if ($patient->do_not_contact || ! $patient->contact_consent) {
            throw ValidationException::withMessages([
                'message' => 'O paciente está marcado para não receber contatos pelo sistema.',
            ]);
        }

        if (! $patient->phone) {
            throw ValidationException::withMessages([
                'message' => 'O paciente não possui WhatsApp cadastrado.',
            ]);
        }

        $message = $meta->sendTextMessage(
            $patient->phone,
            trim($data['message']),
            null,
            $patient->id
        );

        $patient->update(['last_contact_at' => now()]);

        return back()->with(
            'success',
            'Mensagem enviada ao paciente. Status: '.$message->status.'.'
        );
    }

    private function validated(Request $request, ?Patient $patient = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'secondary_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'preferred_contact_channel' => ['required', Rule::in(['whatsapp','phone','email'])],
            'contact_consent' => ['nullable', 'boolean'],
            'do_not_contact' => ['nullable', 'boolean'],
            'cpf' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('patients', 'cpf')->ignore($patient?->id),
            ],
            'rgea_number' => [
                'required',
                'string',
                'max:40',
                Rule::unique('patients', 'rgea_number')->ignore($patient?->id),
            ],
            'birth_date' => ['nullable', 'date'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:30'],
            'address_complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'size:2'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
