<?php

namespace App\Http\Controllers;

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
        abort_unless(
            auth()->check() && auth()->user()->isAdmin(),
            403,
            'Acesso permitido somente para administradores.'
        );
    }

    public function index()
    {
        $this->ensureAdmin();

        $users = User::query()
            ->orderByRaw("
                CASE role
                    WHEN 'admin' THEN 1
                    WHEN 'supervisor' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],

            'username' => [
                'required',
                'string',
                'min:3',
                'max:80',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],

            'email' => [
                'nullable',
                'email',
                'max:190',
                Rule::unique('users', 'email'),
            ],

            'role' => [
                'required',
                Rule::in(['admin', 'supervisor', 'attendant']),
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ]);

        User::create([
            'name' => trim($validated['name']),
            'username' => Str::lower(trim($validated['username'])),
            'email' => $validated['email'] ?: null,
            'role' => $validated['role'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Usuário criado com sucesso.');
    }

    public function toggleStatus(User $user)
    {
        $this->ensureAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'user' => 'Você não pode desativar seu próprio usuário.',
            ]);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? 'Usuário ativado com sucesso.'
                : 'Usuário desativado com sucesso.'
        );
    }

    public function updatePassword(Request $request, User $user)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with(
            'success',
            "Senha de {$user->name} alterada com sucesso."
        );
    }
}
