@extends('enfas.layout')

@section('title','Preferências da agenda')
@section('page_kicker','Agenda ENFAS')
@section('page_title','Preferências da agenda')
@section(
    'page_subtitle',
    'Configure comportamento da agenda e a régua multicanal de confirmação.'
)

@section('content')

@if(session('success'))
    <div class="ea-flash">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Revise os campos abaixo.</strong>
        <div class="small mt-1">
            {{ $errors->first() }}
        </div>
    </div>
@endif

@php
    $selectedProfessionals =
        collect(
            $confirmationPolicy['voice_professional_ids']
            ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->all();

    $selectedServices =
        collect(
            $confirmationPolicy['voice_service_ids']
            ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->all();

    $voiceReady =
        $voiceSystem['configured']
        && $voiceSystem['webhook_validation'];

    $voiceStateLabel =
        $voiceSystem['enabled']
            ? (
                $voiceReady
                    ? 'Ativo'
                    : 'Configuração incompleta'
            )
            : (
                $voiceReady
                    ? 'Pronto para ativação controlada'
                    : 'Modo seguro'
            );
@endphp

<div class="ea-settings-grid mb-3">
    <section class="ea-settings-summary">
        <div class="ea-settings-summary-icon">
            <i class="bi bi-calendar3"></i>
        </div>

        <div>
            <span class="ea-ops-eyebrow">
                Agenda
            </span>

            <strong>
                Operação diária
            </strong>

            <small>
                {{ $start }}–{{ $end }}
                · slots de {{ $slot }} min
            </small>
        </div>
    </section>

    <section class="ea-settings-summary">
        <div class="ea-settings-summary-icon is-whatsapp">
            <i class="bi bi-whatsapp"></i>
        </div>

        <div>
            <span class="ea-ops-eyebrow">
                WhatsApp
            </span>

            <strong>
                {{
                    $reminders
                        ? 'Lembretes ativos'
                        : 'Lembretes pausados'
                }}
            </strong>

            <small>
                Primeiro canal da régua de confirmação.
            </small>
        </div>
    </section>

    <section class="ea-settings-summary">
        <div class="ea-settings-summary-icon is-voice">
            <i class="bi bi-telephone"></i>
        </div>

        <div>
            <span class="ea-ops-eyebrow">
                Voz · {{ strtoupper($voiceSystem['provider']) }}
            </span>

            <strong>
                {{ $voiceStateLabel }}
            </strong>

            <small>
                Assinatura webhook:
                {{
                    $voiceSystem['webhook_validation']
                        ? 'ativa'
                        : 'inativa'
                }}
            </small>
        </div>
    </section>
</div>

<form
    method="POST"
    action="{{ route('enfas.agenda-settings.save') }}"
>
    @csrf

    <div class="ea-settings-layout">
        <div class="ea-settings-main">

            <section class="ea-settings-card">
                <div class="ea-settings-card-head">
                    <div>
                        <span class="ea-ops-eyebrow">
                            Agenda
                        </span>

                        <h3>
                            Operação padrão
                        </h3>

                        <p>
                            Parâmetros utilizados pela recepção e pelo calendário.
                        </p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            Intervalo padrão
                        </label>

                        <div class="input-group">
                            <input
                                class="form-control"
                                type="number"
                                name="default_slot"
                                value="{{ old('default_slot', $slot) }}"
                                min="5"
                                max="240"
                                required
                            >

                            <span class="input-group-text">
                                min
                            </span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Início do dia
                        </label>

                        <input
                            class="form-control"
                            type="time"
                            name="day_start"
                            value="{{ old('day_start', $start) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Fim do dia
                        </label>

                        <input
                            class="form-control"
                            type="time"
                            name="day_end"
                            value="{{ old('day_end', $end) }}"
                            required
                        >
                    </div>
                </div>

                <div class="ea-setting-switches">
                    <label class="ea-setting-switch">
                        <span>
                            <strong>Solicitar confirmação</strong>
                            <small>Habilita a jornada de confirmação do agendamento.</small>
                        </span>

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="confirmation_enabled"
                            value="1"
                            @checked(
                                old(
                                    'confirmation_enabled',
                                    $confirmation
                                )
                            )
                        >
                    </label>

                    <label class="ea-setting-switch">
                        <span>
                            <strong>Lembretes automáticos</strong>
                            <small>Mantém as automações de lembrete da Agenda.</small>
                        </span>

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="reminders_enabled"
                            value="1"
                            @checked(
                                old(
                                    'reminders_enabled',
                                    $reminders
                                )
                            )
                        >
                    </label>
                </div>
            </section>

            <section class="ea-settings-card">
                <div class="ea-settings-card-head">
                    <div>
                        <span class="ea-ops-eyebrow">
                            Régua estilo Nina
                        </span>

                        <h3>
                            Escalonamento inteligente
                        </h3>

                        <p>
                            WhatsApp primeiro; voz apenas quando a confirmação continuar pendente.
                        </p>
                    </div>

                    <span class="ea-status neutral">
                        Voz real {{
                            $voiceSystem['enabled']
                                ? 'ON'
                                : 'OFF'
                        }}
                    </span>
                </div>

                <div class="ea-setting-switches mb-3">
                    <label class="ea-setting-switch">
                        <span>
                            <strong>Fallback para ligação</strong>
                            <small>Permite criar fila de voz após falha ou ausência de resposta.</small>
                        </span>

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="voice_fallback_enabled"
                            value="1"
                            @checked(
                                old(
                                    'voice_fallback_enabled',
                                    $confirmationPolicy['voice_fallback_enabled']
                                )
                            )
                        >
                    </label>

                    <label class="ea-setting-switch">
                        <span>
                            <strong>Fallback humano</strong>
                            <small>Cria tarefa para a equipe quando a automação não puder concluir.</small>
                        </span>

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="human_fallback_enabled"
                            value="1"
                            @checked(
                                old(
                                    'human_fallback_enabled',
                                    $confirmationPolicy['human_fallback_enabled']
                                )
                            )
                        >
                    </label>

                    <label class="ea-setting-switch">
                        <span>
                            <strong>Respeitar consentimento</strong>
                            <small>Bloqueia voz para paciente sem consentimento ou marcado como não contatar.</small>
                        </span>

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="respect_contact_consent"
                            value="1"
                            @checked(
                                old(
                                    'respect_contact_consent',
                                    $confirmationPolicy['respect_contact_consent']
                                )
                            )
                        >
                    </label>
                </div>

                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label">
                            Escalar para voz
                        </label>

                        <div class="input-group">
                            <input
                                class="form-control"
                                type="number"
                                name="voice_escalation_minutes"
                                min="15"
                                max="1440"
                                value="{{
                                    old(
                                        'voice_escalation_minutes',
                                        $confirmationPolicy['voice_escalation_minutes']
                                    )
                                }}"
                                required
                            >

                            <span class="input-group-text">
                                min
                            </span>
                        </div>

                        <div class="form-text">
                            Antes do horário, se continuar sem resposta.
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <label class="form-label">
                            Sem WhatsApp
                        </label>

                        <div class="input-group">
                            <input
                                class="form-control"
                                type="number"
                                name="voice_no_whatsapp_minutes"
                                min="30"
                                max="2880"
                                value="{{
                                    old(
                                        'voice_no_whatsapp_minutes',
                                        $confirmationPolicy['voice_no_whatsapp_minutes']
                                    )
                                }}"
                                required
                            >

                            <span class="input-group-text">
                                min
                            </span>
                        </div>

                        <div class="form-text">
                            Janela antecipada quando nenhum WhatsApp foi enviado.
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <label class="form-label">
                            Intervalo entre tentativas
                        </label>

                        <div class="input-group">
                            <input
                                class="form-control"
                                type="number"
                                name="voice_retry_minutes"
                                min="5"
                                max="1440"
                                value="{{
                                    old(
                                        'voice_retry_minutes',
                                        $confirmationPolicy['voice_retry_minutes']
                                    )
                                }}"
                                required
                            >

                            <span class="input-group-text">
                                min
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <label class="form-label">
                            Máximo de tentativas
                        </label>

                        <input
                            class="form-control"
                            type="number"
                            name="voice_max_attempts"
                            min="1"
                            max="10"
                            value="{{
                                old(
                                    'voice_max_attempts',
                                    $confirmationPolicy['voice_max_attempts']
                                )
                            }}"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Ligações permitidas a partir de
                        </label>

                        <input
                            class="form-control"
                            type="time"
                            name="voice_allowed_start"
                            value="{{
                                old(
                                    'voice_allowed_start',
                                    $confirmationPolicy['voice_allowed_start']
                                )
                            }}"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Ligações permitidas até
                        </label>

                        <input
                            class="form-control"
                            type="time"
                            name="voice_allowed_end"
                            value="{{
                                old(
                                    'voice_allowed_end',
                                    $confirmationPolicy['voice_allowed_end']
                                )
                            }}"
                            required
                        >
                    </div>
                </div>
            </section>

            <section class="ea-settings-card">
                <div class="ea-settings-card-head">
                    <div>
                        <span class="ea-ops-eyebrow">
                            Escopo
                        </span>

                        <h3>
                            Profissionais e serviços
                        </h3>

                        <p>
                            Sem seleção significa todos. Selecione apenas quando quiser limitar a régua de voz.
                        </p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">
                            Profissionais habilitados
                        </label>

                        <select
                            class="form-select ea-settings-multiselect"
                            name="voice_professional_ids[]"
                            multiple
                            size="8"
                        >
                            @foreach($professionals as $professional)
                                <option
                                    value="{{ $professional->id }}"
                                    @selected(
                                        in_array(
                                            (int) $professional->id,
                                            old(
                                                'voice_professional_ids',
                                                $selectedProfessionals
                                            ),
                                            true
                                        )
                                    )
                                >
                                    {{ $professional->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="form-text">
                            Nenhum selecionado = todos os profissionais.
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">
                            Serviços habilitados
                        </label>

                        <select
                            class="form-select ea-settings-multiselect"
                            name="voice_service_ids[]"
                            multiple
                            size="8"
                        >
                            @foreach($services as $service)
                                <option
                                    value="{{ $service->id }}"
                                    @selected(
                                        in_array(
                                            (int) $service->id,
                                            old(
                                                'voice_service_ids',
                                                $selectedServices
                                            ),
                                            true
                                        )
                                    )
                                >
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="form-text">
                            Nenhum selecionado = todos os serviços.
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="ea-settings-side">
            <section class="ea-settings-card is-sticky">
                <span class="ea-ops-eyebrow">
                    Segurança
                </span>

                <h3>
                    Estado do canal de voz
                </h3>

                <div class="ea-settings-status-list">
                    <div>
                        <span>Provider</span>
                        <strong>
                            {{ strtoupper($voiceSystem['provider']) }}
                        </strong>
                    </div>

                    <div>
                        <span>Credenciais</span>
                        <strong>
                            {{
                                $voiceSystem['configured']
                                    ? 'Presentes'
                                    : 'Pendentes'
                            }}
                        </strong>
                    </div>

                    <div>
                        <span>Webhook assinado</span>
                        <strong>
                            {{
                                $voiceSystem['webhook_validation']
                                    ? 'Sim'
                                    : 'Não'
                            }}
                        </strong>
                    </div>

                    <div>
                        <span>Chamadas reais</span>
                        <strong class="{{
                            $voiceSystem['enabled']
                                ? 'text-danger'
                                : 'text-success'
                        }}">
                            {{
                                $voiceSystem['enabled']
                                    ? 'Ativadas'
                                    : 'Desativadas'
                            }}
                        </strong>
                    </div>
                </div>

                <div class="ea-settings-note">
                    <i class="bi bi-shield-check"></i>

                    <span>
                        Esta tela configura a política. A ativação técnica de chamadas reais continua separada e controlada.
                    </span>
                </div>

                <button
                    class="btn btn-primary w-100 mt-3"
                    type="submit"
                >
                    <i class="bi bi-check2-circle me-1"></i>
                    Salvar régua
                </button>
            </section>
        </aside>
    </div>
</form>

@endsection
