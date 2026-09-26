<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string'],
        ]);

        $username = Str::lower(trim($credentials['username']));

        $authenticated = Auth::attempt([
            'username' => $username,
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $authenticated) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Usuário ou senha inválidos.',
                ]);
        }

        $request->session()->regenerate();

        Auth::user()->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function showPasswordChange()
    {
        return view('auth.change-password');
    }

    public function updateOwnPassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required','current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->letters()->numbers(),
            ],
        ]);

        $user = $request->user();

        abort_unless($user && $user->is_active, 403);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'force_password_change' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Senha atualizada com segurança.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
