@extends('enfas.layout')

@section('title','WhatsApp')
@section('page_title','WhatsApp')
@section(
  'page_subtitle',
  'Confirmações, lembretes e comunicação dos agendamentos.'
)

@section('content')

@if(session('success'))
<div class="alert alert-success">
  <i class="bi bi-check-circle me-2"></i>
  {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
  <i class="bi bi-exclamation-triangle me-2"></i>
  {{ $errors->first() }}
</div>
@endif

@php
$connected = (bool) $integration?->last_tested_at;
@endphp

<div class="card ea-wa-panel mb-4">
  <div class="card-body p-4">

    <div class="d-flex flex-wrap align-items-center gap-3">

      <div class="ea-wa-brand">
        <i class="bi bi-whatsapp"></i>
      </div>

      <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2">
          <h5 class="mb-0 fw-bold">
            {{ $connected ? 'WhatsApp conectado' : 'WhatsApp requer configuração' }}
          </h5>
          <span class="ea-status-dot {{ $connected ? 'ok' : 'warn' }}"></span>
        </div>

        <div class="small text-secondary mt-1">
          @if($connected)
            Conta oficial conectada à Meta Cloud API.
          @else
            Salve ou atualize as credenciais para validar a conexão.
          @endif
        </div>
      </div>

      @if($integration)
      <form method="POST" action="{{ url('/whatsapp/testar') }}">
        @csrf
        <button class="btn btn-outline-success">
          <i class="bi bi-cloud-check me-1"></i>
          Testar conexão
        </button>
      </form>
      @endif

    </div>

    <div class="row g-3 mt-2">

      <div class="col-md-4">
        <div class="ea-kpi">
          <span>Número conectado</span>
          <strong style="font-size:1rem">
            {{ $integration?->display_phone_number ?: '—' }}
          </strong>
        </div>
      </div>

      <div class="col-md-4">
        <div class="ea-kpi">
          <span>Nome verificado</span>
          <strong style="font-size:1rem">
            {{ $integration?->verified_name ?: '—' }}
          </strong>
        </div>
      </div>

      <div class="col-md-4">
        <div class="ea-kpi">
          <span>Qualidade</span>
          <strong style="font-size:1rem">
            {{ $integration?->quality_rating ?: '—' }}
          </strong>
        </div>
      </div>

    </div>

  </div>
</div>

<div class="row g-3 mb-4">

  <div class="col-md-4">
    <a href="{{ url('/whatsapp/templates') }}" class="ea-quick h-100">
      <i class="bi bi-chat-square-text fs-4"></i>
      <div>
        <strong class="d-block">Modelos</strong>
        <small class="text-secondary">
          Criar, sincronizar e acompanhar aprovação.
        </small>
      </div>
    </a>
  </div>

  <div class="col-md-4">
    <a href="{{ url('/whatsapp/automacoes') }}" class="ea-quick h-100">
      <i class="bi bi-lightning-charge fs-4"></i>
      <div>
        <strong class="d-block">Automações</strong>
        <small class="text-secondary">
          Confirmações e lembretes automáticos.
        </small>
      </div>
    </a>
  </div>

  <div class="col-md-4">
    <a href="{{ url('/whatsapp/mensagens') }}" class="ea-quick h-100">
      <i class="bi bi-clock-history fs-4"></i>
      <div>
        <strong class="d-block">Histórico</strong>
        <small class="text-secondary">
          Enviado, entregue, lido e falhas.
        </small>
      </div>
    </a>
  </div>

</div>

<details class="card">
  <summary
    class="card-header d-flex align-items-center gap-2"
    style="cursor:pointer;list-style:none">
    <i class="bi bi-gear"></i>
    <strong>Configuração avançada da Meta</strong>
    <i class="bi bi-chevron-down ms-auto"></i>
  </summary>

  <div class="card-body">

    <form method="POST" action="{{ url('/whatsapp') }}">
      @csrf

      <div class="row g-3">

        <div class="col-md-5">
          <label class="form-label">Nome da conexão</label>
          <input
            class="form-control"
            name="name"
            value="{{ $integration?->name ?? 'WhatsApp principal' }}"
            required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Business ID</label>
          <input
            class="form-control"
            name="business_id"
            value="{{ $integration?->business_id }}">
        </div>

        <div class="col-md-2">
          <label class="form-label">Graph API</label>
          <input
            class="form-control"
            name="graph_version"
            value="{{ $integration?->graph_version ?? 'v25.0' }}"
            required>
        </div>

        <div class="col-md-6">
          <label class="form-label">WABA ID</label>
          <input
            class="form-control"
            name="waba_id"
            value="{{ $integration?->waba_id }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Phone Number ID</label>
          <input
            class="form-control"
            name="phone_number_id"
            value="{{ $integration?->phone_number_id }}">
        </div>

        <div class="col-12">
          <label class="form-label">System User Access Token</label>
          <input
            class="form-control"
            type="password"
            name="access_token"
            autocomplete="new-password"
            placeholder="{{ $integration?->access_token ? 'Token já salvo — deixe vazio para manter' : 'Cole o novo token' }}">

          <small class="text-secondary">
            O sistema nunca mostra o token salvo nesta tela.
          </small>
        </div>

        <div class="col-md-6">
          <label class="form-label">App Secret</label>
          <input
            class="form-control"
            type="password"
            name="app_secret"
            autocomplete="new-password"
            placeholder="{{ $integration?->app_secret ? 'Já salvo — deixe vazio para manter' : 'Informe o App Secret' }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Verify Token</label>
          <input
            class="form-control"
            type="password"
            name="verify_token"
            autocomplete="new-password"
            placeholder="{{ $integration?->verify_token ? 'Já salvo — deixe vazio para manter' : 'Informe o Verify Token' }}">
        </div>

      </div>

      <div class="d-flex justify-content-end mt-4">
        <button class="btn btn-primary px-4">
          Salvar configuração
        </button>
      </div>

    </form>

  </div>
</details>

@stop
