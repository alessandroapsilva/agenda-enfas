<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MicrosoftAuthController extends Controller
{
    public function redirect(Request $request)
    {
        abort_unless($this->configured(), 503, 'Login Microsoft ainda não está configurado.');

        $state = Str::random(64);
        $verifier = Str::random(96);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $request->session()->put('microsoft_oauth_state', $state);
        $request->session()->put('microsoft_oauth_verifier', $verifier);

        $query = http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ]);

        return redirect()->away($this->authority().'/oauth2/v2.0/authorize?'.$query);
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'username' => 'Microsoft recusou o login: '.($request->string('error_description')->toString() ?: $request->string('error')->toString()),
            ]);
        }

        $state = (string) $request->session()->pull('microsoft_oauth_state', '');
        $verifier = (string) $request->session()->pull('microsoft_oauth_verifier', '');

        abort_unless(
            $state !== ''
            && $request->filled('state')
            && hash_equals($state, $request->string('state')->toString()),
            419,
            'Sessão de autenticação Microsoft inválida.'
        );

        $code = $request->string('code')->toString();
        abort_if($code === '' || $verifier === '', 422, 'Código de autenticação ausente.');

        try {
            $token = Http::asForm()
                ->timeout(15)
                ->post($this->authority().'/oauth2/v2.0/token', [
                    'client_id' => config('services.microsoft.client_id'),
                    'client_secret' => config('services.microsoft.client_secret'),
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri(),
                    'scope' => 'openid profile email User.Read',
                    'code_verifier' => $verifier,
                ])
                ->throw()
                ->json();

            $accessToken = $token['access_token'] ?? null;
            if (! $accessToken) {
                throw new RuntimeException('Token Microsoft ausente.');
            }

            $profile = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(15)
                ->get('https://graph.microsoft.com/v1.0/me', [
                    '$select' => 'id,displayName,mail,userPrincipalName',
                ])
                ->throw()
                ->json();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'username' => 'Não foi possível concluir o login Microsoft. Tente novamente.',
            ]);
        }

        $oid = trim((string) ($profile['id'] ?? ''));
        $email = Str::lower(trim((string) ($profile['mail'] ?? $profile['userPrincipalName'] ?? '')));
        $name = trim((string) ($profile['displayName'] ?? ''));

        if ($oid === '' || $email === '') {
            return redirect()->route('login')->withErrors([
                'username' => 'A conta Microsoft não forneceu identificação e e-mail válidos.',
            ]);
        }

        $user = User::query()->where('microsoft_oid', $oid)->first();

        if (! $user) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        if (! $user && config('services.microsoft.auto_provision', false)) {
            $base = Str::slug(Str::before($email, '@'), '.');
            $username = $base !== '' ? $base : 'microsoft';
            $suffix = 1;

            while (User::where('username', $username)->exists()) {
                $username = $base.'.'.$suffix++;
            }

            $user = User::create([
                'name' => $name !== '' ? $name : $email,
                'username' => $username,
                'email' => $email,
                'password' => Str::random(64),
                'role' => config('services.microsoft.default_role', 'attendant'),
                'is_active' => true,
                'force_password_change' => false,
            ]);
        }

        if (! $user || ! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'username' => 'Sua conta Microsoft não está vinculada a um usuário ativo do ENFAS.',
            ]);
        }

        if ($user->microsoft_oid && ! hash_equals((string) $user->microsoft_oid, $oid)) {
            return redirect()->route('login')->withErrors([
                'username' => 'Este usuário já está vinculado a outra identidade Microsoft.',
            ]);
        }

        $user->forceFill([
            'microsoft_oid' => $oid,
            'microsoft_tenant_id' => (string) config('services.microsoft.tenant'),
            'microsoft_linked_at' => $user->microsoft_linked_at ?: now(),
            'last_login_at' => now(),
            'last_login_provider' => 'microsoft',
        ])->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function configured(): bool
    {
        return (bool) config('services.microsoft.enabled')
            && filled(config('services.microsoft.client_id'))
            && filled(config('services.microsoft.client_secret'))
            && filled(config('services.microsoft.tenant'));
    }

    private function authority(): string
    {
        return 'https://login.microsoftonline.com/'.rawurlencode((string) config('services.microsoft.tenant', 'organizations'));
    }

    private function redirectUri(): string
    {
        return (string) (config('services.microsoft.redirect_uri') ?: route('microsoft.callback'));
    }
}
