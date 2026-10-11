<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])
            ->where('is_active', true)
            ->first();

        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with(
            'status',
            'Se o e-mail estiver vinculado a um usuário ativo, enviaremos as instruções para redefinir a senha.'
        );
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:190'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(10)->letters()->numbers(),
            ],
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                abort_unless($user->is_active, 403);

                $user->forceFill([
                    'password' => Hash::make($password),
                    'force_password_change' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', 'Senha redefinida com sucesso. Você já pode entrar.');
    }
}
