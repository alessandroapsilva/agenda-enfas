@extends('layouts.app')

@section('title','Prescrições')
@section('page_kicker','ATENDIMENTO CLÍNICO')
@section('page_title','Prescrições')
@section('page_subtitle','Receitas estruturadas, rastreáveis e vinculadas ao prontuário do paciente.')

@section('page_actions')
@if($appointment)
<a href="{{ route('appointments.record',$appointment) }}" class="btn btn-light border">
    <i class="bi bi-journal-medical"></i>Prontuário
</a>
@endif
<a href="{{ route('clinical-documents.index', array_filter(['patient_id'=>$patient?->id,'appointment_id'=>$appointment?->id])) }}" class="btn btn-light border">
    <i class="bi bi-file-earmark-medical"></i>Documentos
</a>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
@endif

<div class="row g-4">
    @can('prescriptions.manage')
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Nova prescrição</strong>
                <span class="small text-secondary">Crie o rascunho e adicione os medicamentos na etapa seguinte.</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('clinical-prescriptions.store') }}">
                    @csrf

                    @if($appointment)
                        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                        <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                        <input type="hidden" name="professional_id" value="{{ $appointment->professional_id }}">

                        <div class="rounded border p-3 mb-3">
                            <div class="small text-secondary">Paciente</div>
                            <strong>{{ $appointment->patient->displayName() }}</strong>
                            <div class="small text-secondary mt-2">Profissional</div>
                            <strong>{{ $appointment->professional->name }}</strong>
                            <div class="small text-secondary mt-2">Atendimento</div>
                            <strong>{{ $appointment->code }} · {{ $appointment->start_at->format('d/m/Y H:i') }}</strong>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label">Paciente *</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">Selecione</option>
                                @foreach($patients as $option)
                                <option value="{{ $option->id }}" @selected((string)old('patient_id',$patient?->id)===(string)$option->id)>
                                    {{ $option->displayName() }}{{ $option->rgea_number ? ' · '.$option->rgea_number : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Profissional *</label>
                            <select name="professional_id" class="form-select" required>
                                <option value="">Selecione</option>
                                @foreach($professionals as $option)
                                <option value="{{ $option->id }}" @selected((string)old('professional_id',auth()->user()->professional_id)===(string)$option->id)>
                                    {{ $option->name }}{{ $option->council_number ? ' · '.$option->council_type.' '.$option->council_number : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input name="title" value="{{ old('title','Prescrição') }}" class="form-control" maxlength="180">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Orientações gerais</label>
                        <textarea name="notes" class="form-control" rows="4" maxlength="8000">{{ old('notes') }}</textarea>
                    </div>

                    <div class="alert alert-light border small">
                        <i class="bi bi-shield-check me-1"></i>
                        Nesta etapa está habilitada a receita simples. Receitas sujeitas a controle especial exigem fluxo regulatório específico antes da ativação.
                    </div>

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg"></i>Criar rascunho
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    <div class="col-12 @can('prescriptions.manage') col-xl-8 @endcan">
        <div class="card">
            <div class="card-header">
                <div>
                    <strong class="d-block">Prescrições recentes</strong>
                    <span class="small text-secondary">
                        @if($patient) Filtradas para {{ $patient->displayName() }}. @else Rascunhos e prescrições emitidas. @endif
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Prescrição</th>
                            <th>Paciente</th>
                            <th>Profissional</th>
                            <th>Itens</th>
                            <th>Status</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($prescriptions as $prescription)
                        <tr>
                            <td>
                                <strong>{{ $prescription->title }}</strong>
                                <div class="small text-secondary">#{{ $prescription->id }} · {{ $prescription->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>{{ $prescription->patient->displayName() }}</td>
                            <td>{{ $prescription->professional->name }}</td>
                            <td>{{ $prescription->items->count() }}</td>
                            <td>
                                @if($prescription->status === 'signed')
                                    <span class="badge text-bg-success">Assinada</span>
                                @else
                                    <span class="badge text-bg-warning">Rascunho</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('clinical-prescriptions.show',$prescription) }}" class="btn btn-sm btn-light border">
                                    <i class="bi bi-arrow-right"></i>Abrir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="enfas-empty py-5">
                                    <strong>Nenhuma prescrição encontrada</strong>
                                    <div>As receitas criadas para este contexto aparecerão aqui.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($prescriptions->hasPages())
            <div class="card-footer">{{ $prescriptions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
