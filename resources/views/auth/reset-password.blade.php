<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nova senha | ENFAS Agenda</title>
    @vite(['resources/css/adminlte.css','resources/css/enfas-agenda.css','resources/js/adminlte.js'])
</head>
<body>
<div class="enfas-login-page">
    <main class="enfas-login-wrap">
        <div class="enfas-login-brand">
            <img class="enfas-login-logo" src="{{ asset('assets/brand/enfas-agenda.svg') }}" alt="ENFAS Agenda">
            <h1>ENFAS Agenda</h1>
            <p>Defina uma nova senha</p>
        </div>

        <section class="card enfas-login-card">
            <div class="card-body">
                <h5 class="fw-semibold mb-1">Redefinir senha</h5>
                <p class="text-secondary small mb-4">Use uma senha forte com pelo menos 10 caracteres.</p>

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.reset') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label class="form-label" for="email">E-mail</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                               class="form-control" autocomplete="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Nova senha</label>
                        <input id="password" type="password" name="password"
                               class="form-control" autocomplete="new-password" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Confirmar nova senha</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               class="form-control" autocomplete="new-password" required>
                    </div>

                    <button class="btn btn-primary w-100 py-2" type="submit">
                        <i class="bi bi-key me-2"></i>Salvar nova senha
                    </button>
                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
