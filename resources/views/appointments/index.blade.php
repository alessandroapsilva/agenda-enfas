@extends('layouts.app')

@section('title', 'Agendamentos')
@section('page_kicker','OPERAÇÃO DA AGENDA')
@section('page_title','Agendamentos')
@section('page_subtitle','Histórico geral, status, unidade, serviço e jornada de cada agendamento.')

@section('page_actions')
<a href="{{ route('agenda.index') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>
    Novo agendamento
</a>
@endsection


@section('content')

<div class="card">
    <div class="card-header">
        <div>
            <strong class="d-block">Histórico de agendamentos</strong>
            <span class="small text-secondary">Visualize rapidamente paciente, tipo, unidade e situação.</span>
        </div>
    </div>

    <div class="table-responsive">

        <table class="table align-middle mb-0">

            <thead>

            <tr>
                <th>Código</th>
                <th>Data</th>
                <th>Paciente</th>
                <th>Tipo</th>
                <th>Serviço</th>
                <th>Profissional</th>
                <th>Unidade</th>
                <th>Status</th>
            </tr>

            </thead>

            <tbody>

            @forelse($appointments as $appointment)

                <tr>

                    <td>
                        <strong>{{ $appointment->code }}</strong>
                    </td>

                    <td>
                        {{ $appointment->start_at->format('d/m/Y H:i') }}
                    </td>

                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="ea-avatar ea-avatar-sm">{{ strtoupper(substr($appointment->patient->name,0,1)) }}</span>
                            <strong>{{ $appointment->patient->name }}</strong>
                        </div>
                    </td>

                    <td>
                        @if($appointment->appointment_type === 'medication_pickup')
                            <span class="badge text-bg-primary"><i class="bi bi-capsule me-1"></i>Retirada</span>
                        @else
                            <span class="badge text-bg-light border">Atendimento</span>
                        @endif
                    </td>

                    <td>
                        {{ $appointment->service->name }}
                    </td>

                    <td>
                        {{ $appointment->professional->name }}
                    </td>

                    <td>
                        {{ $appointment->location?->name ?: '—' }}
                    </td>

                    <td>

                        <span class="badge text-bg-{{ $appointment->statusBadge() }}">

                            {{ $appointment->statusLabel() }}

                        </span>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="8"
                        class="text-center py-5 text-secondary">

                        Nenhum agendamento cadastrado.

                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div class="card-footer">
        {{ $appointments->links() }}
    </div>

</div>

@stop
