@extends('enfas.layout')

@section('title','Confirmações automáticas')
@section('page_title','Confirmações automáticas')
@section(
    'page_subtitle',
    'Escolha em que momento cada paciente deve receber uma confirmação ou lembrete.'
)

@section('content')

@php
$rows = $rows ?? collect();
$templates = $templates ?? collect();
$services = $services ?? collect();

$triggers = [
    'appointment_created' => 'Assim que o horário for criado',
    'appointment_before' => 'Antes do atendimento',
    'appointment_confirmed' => 'Depois da confirmação',
    'appointment_rescheduled' => 'Quando houver reagendamento',
    'appointment_cancelled' => 'Quando houver cancelamento',
    'appointment_completed' => 'Depois do atendimento',
    'appointment_return_due' => 'Na data prevista de retorno',
];

$purposes = [
    'confirmation' => 'Confirmação de presença',
    'reminder' => 'Lembrete',
    'reschedule' => 'Reagendamento',
    'cancellation' => 'Cancelamento',
    'post_service' => 'Após o atendimento',
    'return' => 'Lembrete de retorno',
    'medication_pickup' => 'Retirada de medicamento',
    'general' => 'Mensagem ao paciente',
];

$active = $rows->where('is_active',1)->count();
@endphp

@if(session('success'))
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    {{ $errors->first() }}
</div>
@endif

<div class="ea-saas-hero">
    <div>
        <div class="ea-saas-hero__eyebrow">
            <i class="bi bi-lightning-charge"></i>
            Confirmação automática
        </div>

        <h2>Acompanhe o paciente até o horário</h2>

        <p>
            Organize a sequência de confirmações e lembretes
            sem precisar entender gatilhos ou nomes técnicos.
        </p>
    </div>

    <div class="ea-saas-hero__actions">
        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#newRule">

            <i class="bi bi-plus-lg"></i>
            Nova regra
        </button>
    </div>
</div>

<div class="ea-kpi-grid">

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Regras configuradas
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-diagram-3"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $rows->count() }}
        </div>
    </div>

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Em funcionamento
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-play-circle"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $active }}
        </div>
    </div>

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Pausadas
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-pause-circle"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $rows->count() - $active }}
        </div>
    </div>

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Mensagens disponíveis
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-chat-square-text"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $templates->count() }}
        </div>
    </div>

</div>

<div class="ea-panel">

    <div class="ea-toolbar">
        <div>
            <div class="fw-semibold">
                Sequência de contato
            </div>

            <div class="small text-secondary mt-1">
                Cada linha representa um contato
                automático com o paciente.
            </div>
        </div>

        <div class="ea-search">
            <i class="bi bi-search"></i>

            <input
                id="ruleSearch"
                class="form-control"
                type="search"
                placeholder="Buscar regra">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">

            <thead>
                <tr>
                    <th>Regra</th>
                    <th>Quando acontece</th>
                    <th>Mensagem</th>
                    <th>Situação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>

            <tbody>

            @forelse($rows as $r)

                @php
                $trigger =
                    $triggers[$r->trigger_event]
                    ?? 'Evento da agenda';

                $purpose =
                    $purposes[$r->template?->purpose ?? 'general']
                    ?? 'Mensagem ao paciente';

                $timing = '';

                if (
                    $r->trigger_event === 'appointment_before'
                ) {
                    if ((int)$r->offset_minutes === 2880) {
                        $timing = ' · 2 dias antes';
                    } elseif ((int)$r->offset_minutes === 1440) {
                        $timing = ' · 1 dia antes';
                    } elseif ((int)$r->offset_minutes === 180) {
                        $timing = ' · 3 horas antes';
                    } elseif ((int)$r->offset_minutes === 120) {
                        $timing = ' · 2 horas antes';
                    } elseif ((int)$r->offset_minutes === 60) {
                        $timing = ' · 1 hora antes';
                    } else {
                        $timing =
                            ' · '
                            .(int)$r->offset_minutes
                            .' minutos antes';
                    }
                }
                @endphp

                <tr
                    data-rule-row
                    data-search="{{ mb_strtolower($r->name.' '.$trigger.' '.$purpose) }}">

                    <td style="min-width:240px">

                        <div class="ea-rule">
                            <div class="ea-rule__node">
                                <i class="bi bi-lightning-charge"></i>
                            </div>

                            <div>
                                <div class="ea-rule__title">
                                    {{ $r->name }}
                                </div>

                                <div class="ea-rule__meta">
                                    {{ $purpose }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <td>
                        {{ $trigger }}{{ $timing }}
                    </td>

                    <td>
                        {{ $purpose }}
                    </td>

                    <td>
                        @if($r->is_active)
                            <span class="badge text-bg-success">
                                Em funcionamento
                            </span>
                        @else
                            <span class="badge text-bg-secondary">
                                Pausada
                            </span>
                        @endif
                    </td>

                    <td class="ea-action-cell">

                        <div class="ea-actions">

                            <form
                                method="POST"
                                action="{{ url('/whatsapp/automacoes/'.$r->id.'/status') }}">

                                @csrf
                                @method('PATCH')

                                <button
                                    class="btn btn-sm {{
                                        $r->is_active
                                            ? 'btn-outline-warning'
                                            : 'btn-outline-primary'
                                    }}">

                                    <i class="bi {{
                                        $r->is_active
                                            ? 'bi-pause'
                                            : 'bi-play'
                                    }}"></i>

                                    {{
                                        $r->is_active
                                            ? 'Pausar'
                                            : 'Ativar'
                                    }}
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ url('/whatsapp/automacoes/'.$r->id) }}"
                                onsubmit="return confirm('Excluir esta regra?')">

                                @csrf
                                @method('DELETE')

                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    title="Excluir">

                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="5">
                        <div class="ea-empty">
                            <i class="bi bi-lightning-charge"></i>

                            <strong>
                                Nenhuma regra configurada
                            </strong>

                            Crie uma regra quando quiser
                            iniciar os contatos automáticos.
                        </div>
                    </td>
                </tr>

            @endforelse

            </tbody>
        </table>
    </div>
</div>


<div
    class="modal fade"
    id="newRule"
    tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <form
            method="POST"
            action="{{ url('/whatsapp/automacoes') }}"
            class="modal-content">

            @csrf

            <input
                type="hidden"
                name="retry_count"
                value="3">

            <div class="modal-header">

                <div>
                    <h5 class="modal-title">
                        Nova regra de contato
                    </h5>

                    <div class="small text-secondary mt-1">
                        Escolha quando o paciente deve
                        receber a mensagem.
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">

                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label">
                            Nome
                        </label>

                        <input
                            class="form-control"
                            name="name"
                            placeholder="Ex.: Confirmação no dia anterior"
                            required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Quando enviar
                        </label>

                        <select
                            class="form-select"
                            name="trigger_event"
                            id="ruleTrigger">

                            @foreach($triggers as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Antecedência
                        </label>

                        <select
                            class="form-select"
                            name="offset_minutes">

                            <option value="0">
                                No mesmo momento
                            </option>

                            <option value="60">
                                1 hora antes
                            </option>

                            <option value="120">
                                2 horas antes
                            </option>

                            <option value="180">
                                3 horas antes
                            </option>

                            <option value="1440">
                                1 dia antes
                            </option>

                            <option value="2880">
                                2 dias antes
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Mensagem
                        </label>

                        <select
                            class="form-select"
                            name="template_id"
                            required>

                            <option value="">
                                Escolha a mensagem
                            </option>

                            @foreach($templates as $template)

                                @php
                                $friendly =
                                    $purposes[$template->purpose ?? 'general']
                                    ?? 'Mensagem ao paciente';
                                @endphp

                                <option value="{{ $template->id }}">
                                    {{ $friendly }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Serviço
                        </label>

                        <select
                            class="form-select"
                            name="service_id">

                            <option value="">
                                Todos os serviços
                            </option>

                            @foreach($services as $service)
                            <option value="{{ $service->id }}">
                                {{ $service->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="is_active"
                                value="1"
                                id="ruleActive">

                            <label
                                class="form-check-label"
                                for="ruleActive">

                                Começar a usar esta regra agora
                            </label>
                        </div>

                        <div class="form-text">
                            Deixe desligado enquanto estiver configurando.
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">
                    Fechar
                </button>

                <button class="btn btn-primary">
                    <i class="bi bi-check2"></i>
                    Salvar regra
                </button>
            </div>
        </form>
    </div>
</div>

@stop


@section('js')
<script>
document.addEventListener(
    'DOMContentLoaded',
    () => {

        const input =
            document.getElementById(
                'ruleSearch'
            );

        input?.addEventListener(
            'input',
            () => {

                const query =
                    input.value
                        .trim()
                        .toLowerCase();

                document
                    .querySelectorAll(
                        '[data-rule-row]'
                    )
                    .forEach((row) => {

                        row.hidden =
                            query
                            && !(
                                row.dataset.search
                                || ''
                            ).includes(query);
                    });
            }
        );
    }
);
</script>
@stop
