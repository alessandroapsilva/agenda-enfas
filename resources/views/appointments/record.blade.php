@extends('layouts.app')

@section('title','Prontuário do atendimento')
@section('page_kicker','ATENDIMENTO CLÍNICO')
@section('page_title','Prontuário do atendimento')
@section('page_subtitle','Registro assistencial vinculado ao agendamento '.$appointment->code.'.')

@section('page_actions')
<a href="{{ route('appointments.index') }}" class="btn btn-light border">
    <i class="bi bi-arrow-left"></i>Agendamentos
</a>
<a href="{{ route('patients.show',$appointment->patient) }}" class="btn btn-light border">
    <i class="bi bi-person-lines-fill"></i>Paciente
</a>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
@endif

<div class="ea-record-shell">
    <aside class="ea-record-summary">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="ea-avatar">{{ strtoupper(substr($appointment->patient->name,0,1)) }}</span>
                    <div class="min-w-0">
                        <strong class="d-block text-truncate">{{ $appointment->patient->name }}</strong>
                        <span class="small text-secondary">{{ $appointment->patient->rgea_number ?: 'RGEA não informado' }}</span>
                    </div>
                </div>

                <dl class="ea-context-list mb-0">
                    <div><dt>Agendamento</dt><dd>{{ $appointment->code }}</dd></div>
                    <div><dt>Data</dt><dd>{{ $appointment->start_at->format('d/m/Y H:i') }}</dd></div>
                    <div><dt>Serviço</dt><dd>{{ $appointment->service->name }}</dd></div>
                    <div><dt>Profissional</dt><dd>{{ $appointment->professional->name }}</dd></div>
                    <div><dt>Unidade</dt><dd>{{ $appointment->location?->name ?: '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong>Status do prontuário</strong>
            </div>
            <div class="card-body">
                @if($record?->isFinalized())
                    <div class="ea-record-status is-finalized">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Finalizado</strong>
                            <span>{{ $record->finalized_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    @if($record->finalizer)
                        <div class="small text-secondary mt-3">Por {{ $record->finalizer->name }}</div>
                    @endif
                @elseif($record)
                    <div class="ea-record-status is-draft">
                        <i class="bi bi-pencil-square"></i>
                        <div><strong>Rascunho</strong><span>Em edição</span></div>
                    </div>
                @else
                    <div class="ea-record-status">
                        <i class="bi bi-file-earmark-medical"></i>
                        <div><strong>Não iniciado</strong><span>Sem registro ainda</span></div>
                    </div>
                @endif
            </div>
        </div>
    </aside>

    <main class="ea-record-main">
        <div class="card">
            <div class="card-header">
                <div>
                    <strong class="d-block">Registro assistencial</strong>
                    <span class="small text-secondary">Salve como rascunho durante o atendimento. Após finalizar, use complementações.</span>
                </div>
            </div>

            <div class="card-body">
                @if(!$record?->isFinalized())
                <form method="POST" action="{{ route('appointments.record.save',$appointment) }}">
                    @csrf @method('PUT')

                    <div class="ea-record-section">
                        <h6>Motivo e histórico</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Motivo do atendimento</label>
                                <textarea name="reason_for_visit" class="form-control" rows="3">{{ old('reason_for_visit',$record?->reason_for_visit) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Histórico / relato</label>
                                <textarea name="history" class="form-control" rows="4">{{ old('history',$record?->history) }}</textarea>
                            </div>
                        </div>
                    </div>

                    @php($vitals = old('vitals',$record?->vitals ?? []))
                    <div class="ea-record-section">
                        <h6>Sinais vitais</h6>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label">PA sistólica</label>
                                <div class="input-group"><input type="number" name="vitals[systolic_bp]" value="{{ $vitals['systolic_bp'] ?? '' }}" class="form-control"><span class="input-group-text">mmHg</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">PA diastólica</label>
                                <div class="input-group"><input type="number" name="vitals[diastolic_bp]" value="{{ $vitals['diastolic_bp'] ?? '' }}" class="form-control"><span class="input-group-text">mmHg</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">FC</label>
                                <div class="input-group"><input type="number" name="vitals[heart_rate]" value="{{ $vitals['heart_rate'] ?? '' }}" class="form-control"><span class="input-group-text">bpm</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">FR</label>
                                <div class="input-group"><input type="number" name="vitals[respiratory_rate]" value="{{ $vitals['respiratory_rate'] ?? '' }}" class="form-control"><span class="input-group-text">irpm</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">SpO₂</label>
                                <div class="input-group"><input type="number" step="0.1" name="vitals[spo2]" value="{{ $vitals['spo2'] ?? '' }}" class="form-control"><span class="input-group-text">%</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Temperatura</label>
                                <div class="input-group"><input type="number" step="0.1" name="vitals[temperature]" value="{{ $vitals['temperature'] ?? '' }}" class="form-control"><span class="input-group-text">°C</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Peso</label>
                                <div class="input-group"><input type="number" step="0.1" name="vitals[weight]" value="{{ $vitals['weight'] ?? '' }}" class="form-control"><span class="input-group-text">kg</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Altura</label>
                                <div class="input-group"><input type="number" step="0.1" name="vitals[height]" value="{{ $vitals['height'] ?? '' }}" class="form-control"><span class="input-group-text">cm</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Glicemia</label>
                                <div class="input-group"><input type="number" step="0.1" name="vitals[glucose]" value="{{ $vitals['glucose'] ?? '' }}" class="form-control"><span class="input-group-text">mg/dL</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="ea-record-section">
                        <h6>Avaliação e evolução</h6>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Avaliação</label><textarea name="assessment" class="form-control" rows="4">{{ old('assessment',$record?->assessment) }}</textarea></div>
                            <div class="col-12"><label class="form-label">Intervenções / procedimentos</label><textarea name="interventions" class="form-control" rows="4">{{ old('interventions',$record?->interventions) }}</textarea></div>
                            <div class="col-12"><label class="form-label">Orientações ao paciente</label><textarea name="guidance" class="form-control" rows="4">{{ old('guidance',$record?->guidance) }}</textarea></div>
                            <div class="col-12"><label class="form-label">Evolução</label><textarea name="evolution" class="form-control" rows="5">{{ old('evolution',$record?->evolution) }}</textarea></div>
                            <div class="col-12"><label class="form-label">Plano / retorno</label><textarea name="follow_up_plan" class="form-control" rows="3">{{ old('follow_up_plan',$record?->follow_up_plan) }}</textarea></div>
                        </div>
                    </div>

                    <div class="ea-record-actions">
                        <button class="btn btn-light border"><i class="bi bi-save"></i>Salvar rascunho</button>
                    </div>
                </form>

                @if($record)
                <form method="POST" action="{{ route('appointments.record.finalize',$appointment) }}" class="mt-3" onsubmit="return confirm('Finalizar este prontuário? Depois disso o conteúdo principal ficará bloqueado para edição.');">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-shield-check"></i>Finalizar prontuário</button>
                </form>
                @endif

                @else
                    <div class="ea-record-readonly">
                        @foreach([
                            'Motivo do atendimento'=>$record->reason_for_visit,
                            'Histórico / relato'=>$record->history,
                            'Avaliação'=>$record->assessment,
                            'Intervenções / procedimentos'=>$record->interventions,
                            'Orientações'=>$record->guidance,
                            'Evolução'=>$record->evolution,
                            'Plano / retorno'=>$record->follow_up_plan,
                        ] as $label=>$value)
                            @if($value)
                            <section>
                                <h6>{{ $label }}</h6>
                                <div>{{ $value }}</div>
                            </section>
                            @endif
                        @endforeach

                        @if(!empty($record->vitals))
                        <section>
                            <h6>Sinais vitais</h6>
                            <div class="ea-vitals-grid">
                                @foreach($record->vitals as $key=>$value)
                                    <div><span>{{ $key }}</span><strong>{{ $value }}</strong></div>
                                @endforeach
                            </div>
                        </section>
                        @endif
                    </div>

                    <div class="card mt-4 border">
                        <div class="card-header"><strong>Complementações</strong></div>
                        <div class="card-body">
                            @forelse($record->addenda as $addendum)
                                <div class="ea-record-addendum">
                                    <div class="small text-secondary mb-2">
                                        {{ $addendum->signed_at?->format('d/m/Y H:i') }}
                                        @if($addendum->author) · {{ $addendum->author->name }} @endif
                                    </div>
                                    <div>{{ $addendum->body }}</div>
                                </div>
                            @empty
                                <div class="small text-secondary mb-3">Nenhuma complementação registrada.</div>
                            @endforelse

                            <form method="POST" action="{{ route('appointments.record.addendum',$appointment) }}">
                                @csrf
                                <label class="form-label">Nova complementação</label>
                                <textarea name="body" class="form-control" rows="4" required></textarea>
                                <button class="btn btn-light border mt-2"><i class="bi bi-plus-lg"></i>Adicionar complementação</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
</div>
@stop
