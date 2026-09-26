@extends('layouts.app')

@section('title', 'Usuários | ENFAS Agenda')
@section('page_kicker', 'SEGURANÇA E ACESSOS')
@section('page_title', 'Usuários e permissões')
@section('page_subtitle', 'Gerencie contas, perfis, vínculo profissional e acessos por módulo.')

@section('content')

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-1">Novo usuário</h5>
                <small class="text-muted">Crie um acesso individual com perfil e permissões.</small>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}" class="vstack gap-3">
                    @csrf

                    <div>
                        <label class="form-label">Nome completo</label>
                        <input class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div>
                        <label class="form-label">Usuário</label>
                        <input class="form-control" name="username" value="{{ old('username') }}" required autocomplete="off" placeholder="ex: recepcao01">
                        <div class="form-text">Letras, números, ponto, hífen ou underline.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input class="form-control" type="email" name="email" value="{{ old('email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp</label>
                            <input class="form-control" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Cargo / função</label>
                        <input class="form-control" name="job_title" value="{{ old('job_title') }}" placeholder="Recepção, Enfermagem, Coordenação...">
                    </div>

                    <div>
                        <label class="form-label">Perfil</label>
                        <select class="form-select" name="role" id="new-user-role" required>
                            <option value="attendant">Atendente</option>
                            <option value="professional">Profissional</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <div id="new-user-professional" class="d-none">
                        <label class="form-label">Vincular ao profissional</label>
                        <select class="form-select" name="professional_id">
                            <option value="">Selecione</option>
                            @foreach($professionals as $professional)
                                <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="form-label mb-1">Permissões específicas</label>
                            <small class="text-muted">Opcional</small>
                        </div>
                        <div class="border rounded-3 p-3" style="max-height: 260px; overflow:auto;">
                            @foreach($permissionCatalog as $key => $label)
                                <label class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}">
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-text">Se não selecionar, o sistema usa as permissões padrão do perfil.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Senha inicial</label>
                            <input class="form-control" type="password" name="password" required autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar senha</label>
                            <input class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="alert alert-light border small mb-0">
                        O usuário será criado ativo e deverá trocar a senha inicial no primeiro acesso.
                    </div>

                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-person-plus me-2"></i>Criar usuário
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Equipe com acesso</h5>
                    <small class="text-muted">{{ $users->count() }} usuário(s) cadastrado(s)</small>
                </div>
                <span class="badge text-bg-light border">{{ $users->where('is_active', true)->count() }} ativos</span>
            </div>

            <div class="card-body p-0">
                @forelse($users as $user)
                    <div class="p-4 border-bottom">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="rounded-3 d-grid place-items-center bg-primary-subtle text-primary fw-bold" style="width:48px;height:48px;display:grid;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <strong>{{ $user->name }}</strong>
                                    <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                    <span class="badge text-bg-light border">{{ $user->roleLabel() }}</span>
                                    @if($user->force_password_change)
                                        <span class="badge text-bg-warning">Troca de senha pendente</span>
                                    @endif
                                </div>

                                <div class="text-muted small mt-1">
                                    @{{ $user->username }}
                                    @if($user->job_title) · {{ $user->job_title }} @endif
                                    @if($user->professional) · Vinculado a {{ $user->professional->name }} @endif
                                </div>

                                <div class="text-muted small mt-1">
                                    {{ $user->email ?: 'Sem e-mail' }}
                                    @if($user->phone) · {{ $user->phone }} @endif
                                    @if($user->last_login_at) · Último acesso {{ $user->last_login_at->format('d/m/Y H:i') }} @endif
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#edit-user-{{ $user->id }}">
                                        <i class="bi bi-sliders me-1"></i>Editar acesso
                                    </button>

                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.status', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">
                                                {{ $user->is_active ? 'Desativar' : 'Ativar' }}
                                            </button>
                                        </form>
                                    @endif

                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#password-user-{{ $user->id }}">
                                        Alterar senha
                                    </button>
                                </div>

                                <div class="collapse mt-3" id="edit-user-{{ $user->id }}">
                                    <div class="border rounded-3 p-3 bg-body-tertiary">
                                        <form method="POST" action="{{ route('users.update', $user) }}" class="row g-3">
                                            @csrf
                                            @method('PATCH')

                                            <div class="col-md-6">
                                                <label class="form-label">Nome</label>
                                                <input class="form-control" name="name" value="{{ $user->name }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Perfil</label>
                                                <select class="form-select" name="role">
                                                    @foreach(['attendant'=>'Atendente','professional'=>'Profissional','supervisor'=>'Supervisor','admin'=>'Administrador'] as $value => $label)
                                                        <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">E-mail</label>
                                                <input class="form-control" type="email" name="email" value="{{ $user->email }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">WhatsApp</label>
                                                <input class="form-control" name="phone" value="{{ $user->phone }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Cargo / função</label>
                                                <input class="form-control" name="job_title" value="{{ $user->job_title }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Profissional vinculado</label>
                                                <select class="form-select" name="professional_id">
                                                    <option value="">Nenhum</option>
                                                    @foreach($professionals as $professional)
                                                        <option value="{{ $professional->id }}" @selected($user->professional_id == $professional->id)>{{ $professional->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Permissões específicas</label>
                                                <div class="row g-2">
                                                    @foreach($permissionCatalog as $key => $label)
                                                        <div class="col-md-6">
                                                            <label class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $user->permissions ?? [], true))>
                                                                <span class="form-check-label">{{ $label }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <button class="btn btn-primary btn-sm" type="submit">Salvar alterações</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="collapse mt-3" id="password-user-{{ $user->id }}">
                                    <div class="border rounded-3 p-3">
                                        <form method="POST" action="{{ route('users.password', $user) }}" class="row g-2">
                                            @csrf
                                            @method('PATCH')
                                            <div class="col-md-5">
                                                <input class="form-control" type="password" name="password" required placeholder="Nova senha">
                                            </div>
                                            <div class="col-md-5">
                                                <input class="form-control" type="password" name="password_confirmation" required placeholder="Confirmar senha">
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-primary w-100" type="submit">Salvar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-center text-muted">Nenhum usuário cadastrado.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('new-user-role');
    const professional = document.getElementById('new-user-professional');

    const syncProfessional = () => {
        professional.classList.toggle('d-none', role.value !== 'professional');
    };

    role.addEventListener('change', syncProfessional);
    syncProfessional();
});
</script>
@endpush
@endsection
