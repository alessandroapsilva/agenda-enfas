@extends('enfas.layout')

@section('title','Visão geral')
@section('page_title','Visão geral')
@section(
  'page_subtitle',
  now()->translatedFormat('l, d \d\e F \d\e Y')
)

@section('content')

<div class="d-flex justify-content-end mb-3">
  <a href="{{ url('/agenda') }}" class="btn btn-primary">
    <i class="bi bi-calendar-plus me-1"></i>
    Abrir agenda
  </a>
</div>

<div class="row g-3 mb-4">

  <div class="col-xl-3 col-md-6">
    <div class="ea-kpi">
      <div class="ea-kpi-head">
        <span>Agendamentos hoje</span>
        <div class="ea-kpi-icon"><i class="bi bi-calendar3"></i></div>
      </div>
      <strong>{{ $metrics['today'] }}</strong>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="ea-kpi">
      <div class="ea-kpi-head">
        <span>Confirmados</span>
        <div class="ea-kpi-icon"><i class="bi bi-check2-circle"></i></div>
      </div>
      <strong>{{ $metrics['confirmed'] }}</strong>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="ea-kpi">
      <div class="ea-kpi-head">
        <span>Aguardando confirmação</span>
        <div class="ea-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
      </div>
      <strong>{{ $metrics['awaiting'] }}</strong>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="ea-kpi">
      <div class="ea-kpi-head">
        <span>Pacientes</span>
        <div class="ea-kpi-icon"><i class="bi bi-people"></i></div>
      </div>
      <strong>{{ $metrics['patients'] }}</strong>
    </div>
  </div>

</div>

<div class="row g-3">

  <div class="col-xl-8">

    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <strong>Agenda de hoje</strong>
          <div class="small text-secondary">
            Próximos atendimentos
          </div>
        </div>

        <a href="{{ url('/agenda') }}" class="btn btn-sm btn-light">
          Ver calendário
        </a>
      </div>

      <div class="table-responsive">
        <table class="table">
          <tbody>
          @forelse($appointments as $a)
            <tr>
              <td style="width:82px">
                <strong>
                  {{ \Carbon\Carbon::parse($a->start_at)->format('H:i') }}
                </strong>
              </td>

              <td>
                <strong>{{ $a->patient_name }}</strong>
                <div class="small text-secondary">
                  {{ $a->service_name }}
                  · {{ $a->professional_name }}
                  @if(!empty($a->location_name))
                    · {{ $a->location_name }}
                  @endif
                </div>
              </td>

              <td class="text-end">
                @php
                  $class = match($a->status) {
                    'confirmed' => 'success',
                    'completed' => 'primary',
                    'cancelled' => 'secondary',
                    'no_show' => 'danger',
                    default => 'warning'
                  };

                  $label = match($a->status) {
                    'confirmed' => 'Confirmado',
                    'completed' => 'Concluído',
                    'cancelled' => 'Cancelado',
                    'no_show' => 'Falta',
                    default => 'Aguardando'
                  };
                @endphp

                <span class="badge text-bg-{{ $class }}">
                  {{ $label }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td>
                <div class="ea-empty">
                  <i class="bi bi-calendar2 fs-2 d-block mb-2"></i>
                  Nenhum atendimento programado para hoje.
                </div>
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <div class="col-xl-4">

    <div class="card ea-wa-panel mb-3">

      <div class="card-body">

        <div class="d-flex align-items-center gap-3 mb-4">
          <div class="ea-wa-brand">
            <i class="bi bi-whatsapp"></i>
          </div>

          <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2">
              <strong>WhatsApp Business</strong>
              <span class="ea-status-dot {{ $wa['connected'] ? 'ok' : 'warn' }}"></span>
            </div>

            <div class="small text-secondary">
              @if($wa['connected'])
                {{ $wa['name'] ?: 'Meta Cloud API' }}
              @else
                Integração requer atenção
              @endif
            </div>
          </div>
        </div>

        <div class="row g-2 mb-3">

          <div class="col-4">
            <div class="text-center p-2 rounded-3 border">
              <strong class="d-block">{{ $wa['templates_approved'] }}</strong>
              <small class="text-secondary">Templates</small>
            </div>
          </div>

          <div class="col-4">
            <div class="text-center p-2 rounded-3 border">
              <strong class="d-block">{{ $wa['messages_today'] }}</strong>
              <small class="text-secondary">Hoje</small>
            </div>
          </div>

          <div class="col-4">
            <div class="text-center p-2 rounded-3 border">
              <strong class="d-block">{{ $wa['failed_today'] }}</strong>
              <small class="text-secondary">Falhas</small>
            </div>
          </div>

        </div>

        <div class="d-grid gap-2">
          <a href="{{ url('/whatsapp') }}" class="ea-quick">
            <i class="bi bi-cloud-check"></i>
            Central Meta
          </a>

          <a href="{{ url('/whatsapp/templates') }}" class="ea-quick">
            <i class="bi bi-chat-square-text"></i>
            Modelos de mensagem
          </a>

          <a href="{{ url('/whatsapp/automacoes') }}" class="ea-quick">
            <i class="bi bi-lightning-charge"></i>
            Automações
          </a>
        </div>

      </div>

    </div>

    <div class="card">
      <div class="card-header">
        <strong>Alertas</strong>
      </div>

      <div class="list-group list-group-flush">
        @forelse($alerts as $alert)
          <a
            href="{{ url('/alertas') }}"
            class="list-group-item list-group-item-action bg-transparent">
            <strong class="small">{{ $alert->title }}</strong>
            <div class="small text-secondary text-truncate">
              {{ $alert->message }}
            </div>
          </a>
        @empty
          <div class="ea-empty py-4">
            <i class="bi bi-check2-circle fs-4 d-block mb-2"></i>
            Nada pendente.
          </div>
        @endforelse
      </div>
    </div>

  </div>

</div>
@stop
