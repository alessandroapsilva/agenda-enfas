<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Definir nova senha | ENFAS Agenda</title>

    @vite([
        'resources/css/adminlte.css',
        'resources/css/enfas-agenda.css',
        'resources/js/adminlte.js'
    ])
</head>
<body>
<div class="enfas-login-page">
    <main class="enfas-login-wrap">
        <div class="enfas-login-brand">
            <img class="enfas-login-logo" src="{{ asset('assets/brand/enfas-agenda.svg') }}" alt="ENFAS Agenda">
            <h1>Segurança da conta</h1>
            <p>Defina uma nova senha para continuar.</p>
        </div>

        <section class="card enfas-login-card">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="enfas-stat-icon"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <h5 class="fw-semibold mb-1">Troca obrigatória de senha</h5>
                        <p class="text-secondary small mb-0">
                            Sua senha inicial é temporária. Crie uma senha pessoal antes de acessar o sistema.
                        </p>
                    </div>
                </div>

                @if(session('warning'))
                    <div class="alert alert-warning small">{{ session('warning') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger small">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="vstack gap-3">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="form-label">Senha atual</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <div>
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="password" class="form-control" required autocomplete="new-password">
                        <div class="form-text">Use pelo menos 10 caracteres, com letras e números.</div>
                    </div>

                    <div>
                        <label class="form-label">Confirmar nova senha</label>
                        <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>

                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-check2-circle me-1"></i>Salvar nova senha
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="btn btn-light border w-100" type="submit">
                        Sair da conta
                    </button>
                </form>
            </div>
        </section>

        <div class="enfas-security-note">
            <i class="bi bi-shield-check me-1"></i>
            Conta individual · acesso auditável
        </div>
    </main>
</div>
</body>
</html>
