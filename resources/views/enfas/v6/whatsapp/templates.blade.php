@extends('enfas.layout')

@section('title','Mensagens automáticas')
@section('page_title','Mensagens automáticas')
@section(
    'page_subtitle',
    'Prepare os textos enviados aos pacientes em cada etapa do agendamento.'
)

@section('content')

@php
$rows = $rows ?? collect();
$apiCreationEnabled = $apiCreationEnabled ?? false;

$purposeLabels = [
    'confirmation' => 'Confirmação de presença',
    'reminder' => 'Lembrete de atendimento',
    'reschedule' => 'Reagendamento',
    'cancellation' => 'Cancelamento',
    'post_service' => 'Após o atendimento',
    'general' => 'Mensagem ao paciente',
];

$statusLabels = [
    'LOCAL' => ['Em edição','secondary'],
    'PENDING' => ['Em análise','warning'],
    'APPROVED' => ['Pronta para uso','success'],
    'REJECTED' => ['Precisa de revisão','danger'],
    'PAUSED' => ['Pausada','warning'],
    'DISABLED' => ['Desativada','secondary'],
];

$approved = $rows->where('status','APPROVED')->count();
$pending = $rows->where('status','PENDING')->count();
$editing = $rows->where('status','LOCAL')->count();
$review = $rows->where('status','REJECTED')->count();
@endphp

@if(session('success'))
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    {{ session('success') }}
</div>
@endif

@if(session('meta_error'))
<div class="alert alert-danger">
    <strong>Não foi possível concluir o envio.</strong>
    <div class="mt-1">{{ session('meta_error') }}</div>
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
            <i class="bi bi-whatsapp"></i>
            Comunicação com pacientes
        </div>

        <h2>Mensagens que acompanham a agenda</h2>

        <p>
            Confirmação, lembrete, reagendamento e outros
            contatos ficam organizados aqui sem expor
            configurações técnicas do WhatsApp.
        </p>
    </div>

    <div class="ea-saas-hero__actions">
        <form
            method="POST"
            action="{{ url('/whatsapp/templates/sincronizar') }}">
            @csrf

            <button class="btn btn-light">
                <i class="bi bi-arrow-repeat"></i>
                Atualizar mensagens
            </button>
        </form>

        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#messageEditor">

            <i class="bi bi-plus-lg"></i>
            Nova mensagem
        </button>
    </div>
</div>

<div class="ea-kpi-grid">

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Prontas para uso
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-check2-circle"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $approved }}
        </div>

        <div class="ea-kpi__detail">
            Podem ser usadas nas confirmações
        </div>
    </div>

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Em análise
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-hourglass-split"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $pending }}
        </div>

        <div class="ea-kpi__detail">
            Aguardando liberação do WhatsApp
        </div>
    </div>

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Em edição
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-pencil-square"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $editing }}
        </div>

        <div class="ea-kpi__detail">
            Ainda não foram enviadas
        </div>
    </div>

    <div class="ea-kpi">
        <div class="ea-kpi__top">
            <span class="ea-kpi__label">
                Precisam de atenção
            </span>

            <span class="ea-kpi__icon">
                <i class="bi bi-exclamation-triangle"></i>
            </span>
        </div>

        <div class="ea-kpi__value">
            {{ $review }}
        </div>

        <div class="ea-kpi__detail">
            Precisam ser ajustadas
        </div>
    </div>

</div>

<div class="ea-panel">

    <div class="ea-toolbar">
        <div class="ea-toolbar__left">
            <div class="ea-search">
                <i class="bi bi-search"></i>

                <input
                    class="form-control"
                    id="messageSearch"
                    type="search"
                    placeholder="Buscar mensagem">
            </div>
        </div>

        <div class="ea-toolbar__right">
            <button
                class="ea-filter is-active"
                type="button"
                data-filter="all">
                Todas
            </button>

            <button
                class="ea-filter"
                type="button"
                data-filter="APPROVED">
                Prontas
            </button>

            <button
                class="ea-filter"
                type="button"
                data-filter="PENDING">
                Em análise
            </button>

            <button
                class="ea-filter"
                type="button"
                data-filter="LOCAL">
                Em edição
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table
            class="table align-middle"
            id="messageTable">

            <thead>
                <tr>
                    <th>Mensagem</th>
                    <th>Quando usar</th>
                    <th>Situação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>

            <tbody>

            @forelse($rows as $row)

                @php
                $purpose =
                    $purposeLabels[$row->purpose]
                    ?? 'Mensagem ao paciente';

                $status =
                    $statusLabels[$row->status]
                    ?? ['Disponível','secondary'];
                @endphp

                <tr
                    data-message-row
                    data-status="{{ $row->status }}"
                    data-search="{{ mb_strtolower($purpose.' '.($row->body ?? '')) }}">

                    <td style="min-width:330px">
                        <div class="fw-semibold">
                            {{ $purpose }}
                        </div>

                        <div class="small text-secondary mt-1">
                            {{
                                \Illuminate\Support\Str::limit(
                                    preg_replace(
                                        '/\{\{\d+\}\}/',
                                        'informação do paciente',
                                        preg_replace(
                                            '/\s+/',
                                            ' ',
                                            $row->body ?? ''
                                        )
                                    ),
                                    115
                                )
                            }}
                        </div>
                    </td>

                    <td>
                        {{ $purpose }}
                    </td>

                    <td>
                        <span class="badge text-bg-{{ $status[1] }}">
                            {{ $status[0] }}
                        </span>
                    </td>

                    <td class="ea-action-cell">
                        <div class="ea-actions">

                            <button
                                type="button"
                                class="btn btn-sm btn-light"
                                data-message-preview="{{ $row->id }}">
                                <i class="bi bi-eye"></i>
                                Ver
                            </button>

                            @if($row->status === 'LOCAL' && $apiCreationEnabled)

                            <form
                                method="POST"
                                action="{{ url('/whatsapp/templates/'.$row->id.'/enviar-meta') }}">
                                @csrf

                                <button class="btn btn-sm btn-primary">
                                    <i class="bi bi-send"></i>
                                    Enviar
                                </button>
                            </form>

                            @endif

                            @if($row->status === 'LOCAL')

                            <form
                                method="POST"
                                action="{{ url('/whatsapp/templates/'.$row->id) }}"
                                onsubmit="return confirm('Excluir esta mensagem?')">

                                @csrf
                                @method('DELETE')

                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>

                            @endif

                        </div>
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="4">
                        <div class="ea-empty">
                            <i class="bi bi-chat-square-text"></i>

                            <strong>
                                Nenhuma mensagem cadastrada
                            </strong>

                            Crie a primeira mensagem para
                            iniciar as confirmações.
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
    id="messageEditor"
    tabindex="-1">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">

        <form
            method="POST"
            action="{{ url('/whatsapp/templates') }}"
            class="modal-content"
            id="humanMessageForm">

            @csrf

            <input
                type="hidden"
                name="name"
                id="wireName">

            <input
                type="hidden"
                name="category"
                value="UTILITY">

            <input
                type="hidden"
                name="language"
                value="pt_BR">

            <input
                type="hidden"
                name="body"
                id="wireBody">

            <input
                type="hidden"
                name="variable_keys_text"
                id="wireKeys">

            <input
                type="hidden"
                name="sample_values_text"
                id="wireSamples">

            <input
                type="hidden"
                name="buttons_text"
                id="wireButtons">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        Nova mensagem
                    </h5>

                    <div class="small text-secondary">
                        Escreva como a mensagem deve
                        aparecer para o paciente.
                    </div>
                </div>

                <button
                    class="btn-close"
                    type="button"
                    data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">
                <div class="row g-4">

                    <div class="col-xl-7">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    Momento da jornada
                                </label>

                                <select
                                    class="form-select"
                                    name="purpose"
                                    id="humanPurpose">

                                    <option value="confirmation">
                                        Confirmação de presença
                                    </option>

                                    <option value="reminder">
                                        Lembrete de atendimento
                                    </option>

                                    <option value="reschedule">
                                        Reagendamento
                                    </option>

                                    <option value="cancellation">
                                        Cancelamento
                                    </option>

                                    <option value="post_service">
                                        Após o atendimento
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Título da mensagem
                                </label>

                                <input
                                    class="form-control"
                                    name="header_text"
                                    id="humanHeader"
                                    placeholder="Ex.: Confirmação do seu horário">
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    Texto enviado ao paciente
                                </label>

                                <textarea
                                    class="form-control"
                                    rows="9"
                                    id="humanBody"
                                    required></textarea>

                                <div class="form-text">
                                    Use os botões abaixo para inserir
                                    dados do paciente sem códigos.
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <button
                                        type="button"
                                        class="ea-filter human-token"
                                        data-token="[Paciente]">
                                        Paciente
                                    </button>

                                    <button
                                        type="button"
                                        class="ea-filter human-token"
                                        data-token="[Data]">
                                        Data
                                    </button>

                                    <button
                                        type="button"
                                        class="ea-filter human-token"
                                        data-token="[Hora]">
                                        Hora
                                    </button>

                                    <button
                                        type="button"
                                        class="ea-filter human-token"
                                        data-token="[Serviço]">
                                        Serviço
                                    </button>

                                    <button
                                        type="button"
                                        class="ea-filter human-token"
                                        data-token="[Profissional]">
                                        Profissional
                                    </button>

                                    <button
                                        type="button"
                                        class="ea-filter human-token"
                                        data-token="[Local]">
                                        Local
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label">
                                    Rodapé
                                </label>

                                <input
                                    class="form-control"
                                    name="footer"
                                    id="humanFooter"
                                    value="Enfermagem Alessandro Silva">
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">
                                    Opções para o paciente
                                </label>

                                <select
                                    class="form-select"
                                    id="humanActions">

                                    <option value="confirm">
                                        Confirmar
                                    </option>

                                    <option value="confirm_reschedule">
                                        Confirmar ou reagendar
                                    </option>

                                    <option value="all">
                                        Confirmar, reagendar ou cancelar
                                    </option>

                                    <option value="none">
                                        Sem opções
                                    </option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="col-xl-5">

                        <div class="ea-whatsapp-stage">

                            <div class="small text-secondary fw-semibold mb-3">
                                COMO O PACIENTE VAI RECEBER
                            </div>

                            <div class="ea-phone">

                                <div class="ea-phone__bar">
                                    <div class="ea-phone__avatar">
                                        <i class="bi bi-heart-pulse"></i>
                                    </div>

                                    <div>
                                        <div class="ea-phone__company">
                                            Enfermagem Alessandro Silva
                                        </div>

                                        <div class="ea-phone__meta">
                                            WhatsApp
                                        </div>
                                    </div>
                                </div>

                                <div class="ea-wa-bubble">

                                    <div class="ea-wa-message">

                                        <div
                                            class="ea-wa-header"
                                            id="humanPreviewHeader">
                                        </div>

                                        <div
                                            class="ea-wa-body"
                                            id="humanPreviewBody">
                                        </div>

                                        <div
                                            class="ea-wa-footer"
                                            id="humanPreviewFooter">
                                        </div>

                                    </div>

                                    <div
                                        class="ea-wa-buttons"
                                        id="humanPreviewButtons">
                                    </div>

                                </div>
                            </div>
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

                <button
                    type="submit"
                    name="action"
                    value="draft"
                    class="btn btn-outline-primary">

                    <i class="bi bi-save"></i>
                    Salvar
                </button>

                <button
                    type="submit"
                    name="action"
                    value="send"
                    class="btn btn-primary"
                    @disabled(!$apiCreationEnabled)>

                    <i class="bi bi-send"></i>
                    Enviar para análise
                </button>

            </div>
        </form>
    </div>
</div>


<script
    type="application/json"
    id="messagePreviewData">
{!! json_encode(
    $rows->map(function ($row) use ($purposeLabels) {
        return [
            'id' => $row->id,
            'title' =>
                $purposeLabels[$row->purpose]
                ?? 'Mensagem ao paciente',

            'header' => $row->header_text ?? '',
            'body' => $row->body ?? '',
            'footer' => $row->footer ?? '',
            'keys' => $row->variable_keys ?? [],
            'buttons' => $row->buttons ?? [],
        ];
    })->values(),
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
) !!}
</script>


<div
    class="modal fade"
    id="messagePreview"
    tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <div>
                    <h5
                        class="modal-title"
                        id="messagePreviewTitle">
                        Mensagem
                    </h5>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">

                <div class="ea-whatsapp-stage">

                    <div class="ea-phone">

                        <div class="ea-phone__bar">
                            <div class="ea-phone__avatar">
                                <i class="bi bi-heart-pulse"></i>
                            </div>

                            <div class="ea-phone__company">
                                Enfermagem Alessandro Silva
                            </div>
                        </div>

                        <div class="ea-wa-bubble">

                            <div class="ea-wa-message">

                                <div
                                    class="ea-wa-header"
                                    id="savedPreviewHeader">
                                </div>

                                <div
                                    class="ea-wa-body"
                                    id="savedPreviewBody">
                                </div>

                                <div
                                    class="ea-wa-footer"
                                    id="savedPreviewFooter">
                                </div>

                            </div>

                            <div
                                class="ea-wa-buttons"
                                id="savedPreviewButtons">
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop


@section('js')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const $ = (id) =>
        document.getElementById(id);

    const body =
        $('humanBody');

    const header =
        $('humanHeader');

    const footer =
        $('humanFooter');

    const purpose =
        $('humanPurpose');

    const actions =
        $('humanActions');

    const definitions = {
        '[Paciente]': {
            key: 'paciente_nome',
            sample: 'João'
        },
        '[Data]': {
            key: 'data',
            sample: '03/09/2026'
        },
        '[Hora]': {
            key: 'hora',
            sample: '14:30'
        },
        '[Serviço]': {
            key: 'servico',
            sample: 'Consulta'
        },
        '[Profissional]': {
            key: 'profissional',
            sample: 'Ana'
        },
        '[Local]': {
            key: 'local',
            sample: 'Unidade Centro'
        }
    };

    const defaults = {
        confirmation: {
            header: 'Confirmação do seu horário',
            body:
                'Olá, [Paciente]! Seu atendimento está agendado para [Data] às [Hora]. Podemos confirmar sua presença?',
            actions: 'all'
        },

        reminder: {
            header: 'Lembrete do seu atendimento',
            body:
                'Olá, [Paciente]! Passando para lembrar do seu atendimento de [Serviço] às [Hora]. Esperamos por você.',
            actions: 'none'
        },

        reschedule: {
            header: 'Novo horário',
            body:
                'Olá, [Paciente]! Seu atendimento foi reagendado para [Data] às [Hora].',
            actions: 'confirm'
        },

        cancellation: {
            header: 'Horário cancelado',
            body:
                'Olá, [Paciente]. Seu atendimento de [Data] às [Hora] foi cancelado. Se precisar, nossa equipe pode ajudar com um novo horário.',
            actions: 'none'
        },

        post_service: {
            header: 'Obrigado pela sua visita',
            body:
                'Olá, [Paciente]! Obrigado por estar conosco hoje. Esperamos que sua experiência tenha sido excelente.',
            actions: 'none'
        }
    };

    const buttonSets = {
        confirm: [
            ['CONFIRMAR','confirm']
        ],

        confirm_reschedule: [
            ['CONFIRMAR','confirm'],
            ['REAGENDAR','reschedule']
        ],

        all: [
            ['CONFIRMAR','confirm'],
            ['REAGENDAR','reschedule'],
            ['CANCELAR','cancel']
        ],

        none: []
    };

    const makeTechnicalBody = () => {

        let value =
            body.value;

        const used = [];

        Object
            .keys(definitions)
            .forEach((token) => {

                if (
                    value.includes(token)
                ) {
                    used.push(token);
                }
            });

        used.forEach(
            (token, index) => {

                const wire =
                    `{{${index + 1}}}`;

                value =
                    value
                        .split(token)
                        .join(wire);
            }
        );

        return {
            value,
            used
        };
    };

    const previewText = () => {

        let value =
            body.value;

        Object
            .entries(definitions)
            .forEach(
                ([token, definition]) => {

                    value =
                        value
                            .split(token)
                            .join(
                                definition.sample
                            );
                }
            );

        return value;
    };

    const renderButtons = (
        target,
        selection
    ) => {

        target.innerHTML = '';

        (
            buttonSets[selection]
            || []
        ).forEach((item) => {

            const node =
                document.createElement(
                    'div'
                );

            node.className =
                'ea-wa-button';

            node.textContent =
                item[0];

            target.appendChild(
                node
            );
        });
    };

    const refresh = () => {

        $('humanPreviewHeader')
            .textContent =
                header.value || '';

        $('humanPreviewBody')
            .textContent =
                previewText();

        $('humanPreviewFooter')
            .textContent =
                footer.value || '';

        renderButtons(
            $('humanPreviewButtons'),
            actions.value
        );
    };

    const applyDefault = () => {

        const item =
            defaults[purpose.value];

        if (!item) {
            return;
        }

        header.value =
            item.header;

        body.value =
            item.body;

        actions.value =
            item.actions;

        refresh();
    };

    purpose.addEventListener(
        'change',
        applyDefault
    );

    [
        body,
        header,
        footer,
        actions
    ].forEach((field) => {

        field.addEventListener(
            'input',
            refresh
        );

        field.addEventListener(
            'change',
            refresh
        );
    });

    document
        .querySelectorAll(
            '.human-token'
        )
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const token =
                        button.dataset.token;

                    const start =
                        body.selectionStart
                        ?? body.value.length;

                    const end =
                        body.selectionEnd
                        ?? body.value.length;

                    body.value =
                        body.value.substring(
                            0,
                            start
                        )
                        + token
                        + body.value.substring(
                            end
                        );

                    body.focus();

                    body.selectionStart =
                        body.selectionEnd =
                            start
                            + token.length;

                    refresh();
                }
            );
        });

    $('humanMessageForm')
        .addEventListener(
            'submit',
            () => {

                const result =
                    makeTechnicalBody();

                const stamp =
                    Date.now();

                $('wireName').value =
                    `enfas_${purpose.value}_${stamp}`;

                $('wireBody').value =
                    result.value;

                $('wireKeys').value =
                    result.used
                        .map(
                            (token) =>
                                definitions[token]
                                    .key
                        )
                        .join(',');

                $('wireSamples').value =
                    result.used
                        .map(
                            (token) =>
                                definitions[token]
                                    .sample
                        )
                        .join(',');

                $('wireButtons').value =
                    (
                        buttonSets[
                            actions.value
                        ] || []
                    )
                        .map(
                            (item) =>
                                `${item[0]}|${item[1]}`
                        )
                        .join('\n');
            }
        );

    applyDefault();


    /* Busca */

    let filter = 'all';

    const applySearch = () => {

        const query =
            (
                $('messageSearch')
                    .value || ''
            )
            .toLowerCase()
            .trim();

        document
            .querySelectorAll(
                '[data-message-row]'
            )
            .forEach((row) => {

                const statusOk =
                    filter === 'all'
                    || row.dataset.status
                        === filter;

                const textOk =
                    !query
                    || (
                        row.dataset.search
                        || ''
                    ).includes(query);

                row.hidden =
                    !(statusOk && textOk);
            });
    };

    $('messageSearch')
        .addEventListener(
            'input',
            applySearch
        );

    document
        .querySelectorAll(
            '[data-filter]'
        )
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    filter =
                        button.dataset.filter;

                    document
                        .querySelectorAll(
                            '[data-filter]'
                        )
                        .forEach(
                            (item) =>
                                item.classList
                                    .remove(
                                        'is-active'
                                    )
                        );

                    button.classList
                        .add(
                            'is-active'
                        );

                    applySearch();
                }
            );
        });


    /* Visualizar mensagem existente */

    const stored =
        JSON.parse(
            $('messagePreviewData')
                ?.textContent
            || '[]'
        );

    const keyLabels = {
        paciente_nome: 'Paciente',
        data: 'Data',
        hora: 'Hora',
        servico: 'Serviço',
        profissional: 'Profissional',
        local: 'Local'
    };

    document
        .querySelectorAll(
            '[data-message-preview]'
        )
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const item =
                        stored.find(
                            (row) =>
                                Number(row.id)
                                ===
                                Number(
                                    button.dataset
                                        .messagePreview
                                )
                        );

                    if (!item) {
                        return;
                    }

                    let text =
                        item.body || '';

                    (
                        item.keys || []
                    ).forEach(
                        (key, index) => {

                            const label =
                                keyLabels[key]
                                || 'Informação';

                            text =
                                text
                                    .split(
                                        `{{${index + 1}}}`
                                    )
                                    .join(
                                        `[${label}]`
                                    );
                        }
                    );

                    $('messagePreviewTitle')
                        .textContent =
                            item.title;

                    $('savedPreviewHeader')
                        .textContent =
                            item.header || '';

                    $('savedPreviewBody')
                        .textContent =
                            text;

                    $('savedPreviewFooter')
                        .textContent =
                            item.footer || '';

                    const target =
                        $('savedPreviewButtons');

                    target.innerHTML = '';

                    (
                        item.buttons || []
                    ).forEach((buttonItem) => {

                        const node =
                            document
                                .createElement(
                                    'div'
                                );

                        node.className =
                            'ea-wa-button';

                        node.textContent =
                            buttonItem.text
                            || 'Responder';

                        target.appendChild(
                            node
                        );
                    });

                    new bootstrap.Modal(
                        $('messagePreview')
                    ).show();
                }
            );
        });
});
</script>
@stop
