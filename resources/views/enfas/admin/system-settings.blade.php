@extends('enfas.layout')

@section('title','Configurações do sistema')
@section('page_kicker','Administração')
@section('page_title','Configurações do sistema')
@section('page_subtitle','Horários de funcionamento, autenticação corporativa e infraestrutura transacional.')

@section('content')
@if(session('success'))
    <div class="ea-flash"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Revise a configuração.</strong>
        <div class="small mt-1">{{ $errors->first() }}</div>
    </div>
@endif

@php
    $dayLabels = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];
@endphp

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-body-secondary mb-1">Microsoft Entra ID</div>
                <div class="h5 mb-2">
                    {{ $microsoftSystem['enabled'] && $microsoftSystem['configured'] ? 'Pronto para login' : 'Configuração pendente' }}
                </div>
                <div class="small text-body-secondary">
                    Tenant: {{ $microsoftSystem['tenant'] ?: 'não definido' }}
                </div>
                <div class="small text-body-secondary">
                    Provisionamento automático: {{ $microsoftSystem['auto_provision'] ? 'ativado' : 'desativado' }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-body-secondary mb-1">E-mail transacional</div>
                <div class="h5 mb-2">{{ $emailSystem['configured'] ? 'Configurado' : 'Pendente' }}</div>
                <div class="small text-body-secondary">
                    {{ strtoupper($emailSystem['mailer']) }} · {{ $emailSystem['from'] ?: 'remetente não definido' }}
                </div>
                <div class="small text-body-secondary mt-1">
                    Usado em confirmação, lembretes e recuperação de senha.
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-body-secondary mb-1">Redirect Microsoft</div>
                <div class="small fw-semibold text-break">{{ $microsoftSystem['redirect_uri'] }}</div>
                <div class="small text-body-secondary mt-2">
                    Cadastre exatamente este endereço no Microsoft Entra.
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update') }}">
    @csrf

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h2 class="h5 mb-1">Horários de funcionamento</h2>
            <p class="text-body-secondary mb-0">
                A agenda só oferece horários dentro da operação da unidade e da disponibilidade do profissional.
            </p>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th class="ps-4">Dia</th>
                        <th>Aberto</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Intervalo início</th>
                        <th class="pe-4">Intervalo fim</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($dayLabels as $day => $label)
                        @php($row = $hours[$day])
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $label }}</td>
                            <td>
                                <input type="hidden" name="hours[{{ $day }}][enabled]" value="0">
                                <input class="form-check-input" type="checkbox"
                                       name="hours[{{ $day }}][enabled]" value="1"
                                       @checked(old("hours.$day.enabled", $row['enabled']))>
                            </td>
                            <td>
                                <input class="form-control" type="time"
                                       name="hours[{{ $day }}][start]"
                                       value="{{ old("hours.$day.start", substr($row['start'],0,5)) }}">
                            </td>
                            <td>
                                <input class="form-control" type="time"
                                       name="hours[{{ $day }}][end]"
                                       value="{{ old("hours.$day.end", substr($row['end'],0,5)) }}">
                            </td>
                            <td>
                                <input class="form-control" type="time"
                                       name="hours[{{ $day }}][break_start]"
                                       value="{{ old("hours.$day.break_start", $row['break_start'] ? substr($row['break_start'],0,5) : '') }}">
                            </td>
                            <td class="pe-4">
                                <input class="form-control" type="time"
                                       name="hours[{{ $day }}][break_end]"
                                       value="{{ old("hours.$day.break_end", $row['break_end'] ? substr($row['break_end'],0,5) : '') }}">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-transparent border-0 p-4 d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2-circle me-1"></i>Salvar configurações
            </button>
        </div>
    </div>
</form>

<div class="alert alert-light border mt-3 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    A disponibilidade final é a interseção entre o funcionamento da unidade, a grade do profissional,
    bloqueios, reservas temporárias e agendamentos já existentes.
</div>
@endsection
