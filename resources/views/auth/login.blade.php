<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar | ENFAS Agenda</title>
    @vite(['resources/css/adminlte.css','resources/css/enfas-agenda.css','resources/js/adminlte.js'])
</head>
<body>
<div class="enfas-login-page">
    <main class="enfas-login-wrap">
        <div class="enfas-login-brand">
            <img class="enfas-login-logo" src="{{ asset('assets/brand/enfas-agenda.svg') }}" alt="ENFAS Agenda">
            <h1>ENFAS Agenda</h1>
            <p>Central segura de atendimento e agendamentos</p>
        </div>

        <section class="card enfas-login-card">
            <div class="card-body">
                <h5 class="fw-semibold mb-1">Acessar o sistema</h5>
                <p class="text-secondary small mb-4">Entre com sua conta ENFAS ou Microsoft corporativa.</p>

                @if(session('status'))
                    <div class="alert alert-success py-2 small">
                        <i class="bi bi-check-circle me-1"></i>{{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                @if(config('services.microsoft.enabled')
                    && config('services.microsoft.client_id')
                    && config('services.microsoft.client_secret'))
                    <a href="{{ route('microsoft.redirect') }}"
                       class="btn btn-outline-dark w-100 py-2 mb-3">
                        <i class="bi bi-microsoft me-2"></i>
                        Entrar com Microsoft
                    </a>

                    <div class="d-flex align-items-center gap-2 mb-3 text-secondary small">
                        <div class="border-top flex-grow-1"></div>
                        <span>ou</span>
                        <div class="border-top flex-grow-1"></div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">Usuário</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input id="username" type="text" name="username"
                                   value="{{ old('username') }}" class="form-control"
                                   placeholder="Digite seu usuário" autocomplete="username"
                                   autofocus required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label">Senha</label>
                            <a href="{{ route('password.request') }}" class="small text-decoration-none">
                                Esqueci minha senha
                            </a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" name="password"
                                   class="form-control" placeholder="Digite sua senha"
                                   autocomplete="current-password" required>
                        </div>
                    </div>

                    <div class="form-check my-4">
                        <input class="form-check-input" type="checkbox" name="remember"
                               value="1" id="remember">
                        <label class="form-check-label small" for="remember">Manter conectado</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                    </button>
                </form>
            </div>
        </section>

        <div class="enfas-security-note">
            <i class="bi bi-shield-lock me-1"></i>
            Ambiente restrito a usuários autorizados
        </div>
    </main>
</div>
</body>
</html>
