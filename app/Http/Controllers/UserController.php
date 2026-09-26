<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->canAccess('users.manage'), 403);
    }

    public function index()
    {
        $this->ensureAdmin();

        $users = User::with('professional')
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'supervisor' THEN 2 WHEN 'professional' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();

        $professionals = Professional::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $permissionCatalog = config('enfas_permissions.catalog', []);

        return view('users.index', compact('users', 'professionals', 'permissionCatalog'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required','string','min:3','max:120'],
            'username' => ['required','string','min:3','max:80','regex:/^[a-zA-Z0-9._-]+$/',Rule::unique('users','username')],
            'email' => ['nullable','email','max:190',Rule::unique('users','email')],
            'phone' => ['nullable','string','max:30'],
            'job_title' => ['nullable','string','max:120'],
            'role' => ['required',Rule::in(['admin','supervisor','professional','attendant'])],
            'professional_id' => ['nullable','integer','exists:professionals,id'],
            'permissions' => ['nullable','array'],
            'permissions.*' => ['string', Rule::in(array_keys(config('enfas_permissions.catalog', [])))],
            'password' => ['required','confirmed',Password::min(10)->letters()->numbers()],
        ]);

        User::create([
            'name' => trim($validated['name']),
            'username' => Str::lower(trim($validated['username'])),
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'role' => $validated['role'],
            'professional_id' => $validated['role'] === 'professional' ? ($validated['professional_id'] ?? null) : null,
            'permissions' => $validated['permissions'] ?? null,
            'password' => $validated['password'],
            'is_active' => true,
            'force_password_change' => true,
        ]);

        return back()->with('success', 'Usuário criado com sucesso.');
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required','string','min:3','max:120'],
            'email' => ['nullable','email','max:190',Rule::unique('users','email')->ignore($user->id)],
            'phone' => ['nullable','string','max:30'],
            'job_title' => ['nullable','string','max:120'],
            'role' => ['required',Rule::in(['admin','supervisor','professional','attendant'])],
            'professional_id' => ['nullable','integer','exists:professionals,id'],
            'permissions' => ['nullable','array'],
            'permissions.*' => ['string', Rule::in(array_keys(config('enfas_permissions.catalog', [])))],
        ]);

        $user->update([
            'name' => trim($validated['name']),
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'role' => $validated['role'],
            'professional_id' => $validated['role'] === 'professional' ? ($validated['professional_id'] ?? null) : null,
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return back()->with('success', 'Usuário atualizado com sucesso.');
    }

    public function toggleStatus(User $user)
    {
        $this->ensureAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Você não pode desativar seu próprio usuário.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Usuário ativado com sucesso.' : 'Usuário desativado com sucesso.');
    }

    public function updatePassword(Request $request, User $user)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'password' => ['required','confirmed',Password::min(10)->letters()->numbers()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => true,
        ]);

        return back()->with('success', "Senha de {$user->name} alterada com sucesso.");
    }
}
