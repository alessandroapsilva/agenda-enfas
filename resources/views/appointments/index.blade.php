@extends('layouts.app')

@section('title', 'Agendamentos')

@section('content_header')

<div class="d-flex justify-content-between align-items-end">

    <div>

        <h3 class="enfas-page-title">
            Agendamentos
        </h3>

        <p class="enfas-page-subtitle">
            Histórico e acompanhamento geral.
        </p>

    </div>

    <a href="{{ route('agenda.index') }}"
       class="btn btn-primary">

        <i class="bi bi-plus-lg me-1"></i>
        Novo agendamento

    </a>

</div>

@stop


@section('content')

<div class="card">

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
                        {{ $appointment->patient->name }}
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

                        <span class="badge text-bg-{{ $appointment->statusBadge() }}">

                            {{ $appointment->statusLabel() }}

                        </span>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7"
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
