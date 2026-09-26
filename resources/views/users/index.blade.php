@extends('layouts.app')

@section('title', 'Usuários | ENFAS Agenda')

@section('content')

<div class="page-header">

    <div>
        <span class="eyebrow">
            ADMINISTRAÇÃO
        </span>

        <h1>Usuários</h1>

        <p>
            Gerencie quem pode acessar o sistema.
        </p>
    </div>

</div>


<div class="users-layout">

    <section class="card">

        <div class="card-header">

            <div>
                <h2>Novo usuário</h2>

                <p>
                    Crie um acesso individual.
                </p>
            </div>

        </div>

        <form
            method="POST"
            action="{{ route('users.store') }}"
            class="admin-form">

            @csrf

            <div class="form-group">
                <label>Nome completo</label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="Nome do colaborador">
            </div>


            <div class="form-group">
                <label>Usuário</label>

                <input
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autocomplete="off"
                    placeholder="ex: recepcao01">

                <small>
                    Letras, números, ponto, hífen ou underline.
                </small>
            </div>


            <div class="form-group">
                <label>E-mail</label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Opcional">
            </div>


            <div class="form-group">
                <label>Perfil</label>

                <select name="role" required>

                    <option value="attendant">
                        Atendente
                    </option>

                    <option value="supervisor">
                        Supervisor
                    </option>

                    <option value="admin">
                        Administrador
                    </option>

                </select>
            </div>


            <div class="form-group">
                <label>Senha inicial</label>

                <input
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password">
            </div>


            <div class="form-group">
                <label>Confirmar senha</label>

                <input
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password">
            </div>


            <button
                type="submit"
                class="btn btn-primary btn-full">
                Criar usuário
            </button>

        </form>

    </section>


    <section class="card users-card">

        <div class="card-header">

            <div>
                <h2>Usuários cadastrados</h2>

                <p>
                    {{ $users->count() }} usuário(s)
                </p>
            </div>

        </div>


        <div class="user-list">

            @foreach($users as $user)

                <article class="user-row">

                    <div class="user-avatar large">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>


                    <div class="user-row-main">

                        <div class="user-title">

                            <strong>
                                {{ $user->name }}
                            </strong>

                            <span class="badge
                                {{ $user->is_active ? 'badge-success' : 'badge-muted' }}">
                                {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                            </span>

                        </div>

                        <div class="user-meta">

                            <span>
                                @{{ $user->username }}
                            </span>

                            <span>•</span>

                            <span>
                                {{ $user->roleLabel() }}
                            </span>

                            @if($user->last_login_at)

                                <span>•</span>

                                <span>
                                    Último acesso
                                    {{ $user->last_login_at->format('d/m/Y H:i') }}
                                </span>

                            @endif

                        </div>


                        <div class="user-actions">

                            @if($user->id !== auth()->id())

                                <form
                                    method="POST"
                                    action="{{ route('users.status', $user) }}">

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="btn btn-secondary btn-small"
                                        type="submit">

                                        {{ $user->is_active
                                            ? 'Desativar'
                                            : 'Ativar'
                                        }}

                                    </button>

                                </form>

                            @endif


                            <details class="password-reset">

                                <summary>
                                    Alterar senha
                                </summary>

                                <form
                                    method="POST"
                                    action="{{ route('users.password', $user) }}">

                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="password"
                                        name="password"
                                        required
                                        minlength="8"
                                        placeholder="Nova senha">

                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        required
                                        minlength="8"
                                        placeholder="Confirmar senha">

                                    <button
                                        class="btn btn-primary btn-small"
                                        type="submit">
                                        Salvar senha
                                    </button>

                                </form>

                            </details>

                        </div>

                    </div>

                </article>

            @endforeach

        </div>

    </section>

</div>

@endsection
