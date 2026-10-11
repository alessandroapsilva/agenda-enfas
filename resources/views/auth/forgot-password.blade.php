<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Recuperar senha | ENFAS Agenda</title>
    @vite(['resources/css/adminlte.css','resources/css/enfas-agenda.css','resources/js/adminlte.js'])
</head>
<body>
<div class="enfas-login-page">
    <main class="enfas-login-wrap">
        <div class="enfas-login-brand">
            <img class="enfas-login-logo" src="{{ asset('assets/brand/enfas-agenda.svg') }}" alt="ENFAS Agenda">
            <h1>ENFAS Agenda</h1>
            <p>Recuperação segura de acesso</p>
        </div>

        <section class="card enfas-login-card">
            <div class="card-body">
                <h5 class="fw-semibold mb-1">Esqueceu sua senha?</h5>
                <p class="text-secondary small mb-4">Informe o e-mail vinculado ao seu usuário.</p>

                @if(session('status'))
                    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label" for="email">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   class="form-control" autocomplete="email" required autofocus
                                   placeholder="seu@email.com">
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-2" type="submit">
                        <i class="bi bi-send me-2"></i>Enviar link de recuperação
                    </button>
                </form>

                <a class="btn btn-link w-100 mt-2" href="{{ route('login') }}">
                    Voltar para o login
                </a>
            </div>
        </section>

        <div class="enfas-security-note">
            <i class="bi bi-shield-lock me-1"></i>
            O sistema não informa se um e-mail existe ou não.
        </div>
    </main>
</div>
</body>
</html>
