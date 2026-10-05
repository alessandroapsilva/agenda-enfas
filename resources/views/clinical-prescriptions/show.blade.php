@extends('layouts.app')

@section('title',$prescription->title)
@section('page_kicker','PRESCRIÇÃO')
@section('page_title',$prescription->title)
@section('page_subtitle','Paciente '.$prescription->patient->displayName().' · Profissional '.$prescription->professional->name)

@section('page_actions')
<a href="{{ route('clinical-prescriptions.index', array_filter(['patient_id'=>$prescription->patient_id,'appointment_id'=>$prescription->appointment_id])) }}" class="btn btn-light border">
    <i class="bi bi-arrow-left"></i>Prescrições
</a>
@if($prescription->appointment)
<a href="{{ route('appointments.record',$prescription->appointment) }}" class="btn btn-light border">
    <i class="bi bi-journal-medical"></i>Prontuário
</a>
@endif
<a href="{{ route('clinical-prescriptions.print',$prescription) }}" target="_blank" class="btn btn-light border">
    <i class="bi bi-printer"></i>Imprimir
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
    <div class="col-12 col-xl-8">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <div>
                    <strong class="d-block">Medicamentos e orientações</strong>
                    <span class="small text-secondary">Itens estruturados da prescrição.</span>
                </div>
                @if($prescription->status === 'signed')
                    <span class="badge text-bg-success"><i class="bi bi-patch-check me-1"></i>Assinada</span>
                @else
                    <span class="badge text-bg-warning">Rascunho</span>
                @endif
            </div>

            <div class="card-body">
                @forelse($prescription->items as $item)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong>{{ $loop->iteration }}. {{ $item->medication_name }}</strong>
                                <div class="small text-secondary">
                                    {{ implode(' · ', array_filter([$item->concentration,$item->dosage_form,$item->route])) }}
                                </div>
                            </div>
                            @if(!$prescription->isLocked())
                            @can('prescriptions.manage')
                            <form method="POST" action="{{ route('clinical-prescriptions.items.destroy',[$prescription,$item]) }}" onsubmit="return confirm('Remover este item?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light border text-danger" title="Remover"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcan
                            @endif
                        </div>

                        <dl class="row small mt-3 mb-0">
                            @if($item->quantity)<dt class="col-sm-3">Quantidade</dt><dd class="col-sm-9">{{ $item->quantity }}</dd>@endif
                            <dt class="col-sm-3">Posologia</dt><dd class="col-sm-9">{{ $item->directions }}</dd>
                            @if($item->duration)<dt class="col-sm-3">Duração</dt><dd class="col-sm-9">{{ $item->duration }}</dd>@endif
                            @if($item->notes)<dt class="col-sm-3">Observações</dt><dd class="col-sm-9">{{ $item->notes }}</dd>@endif
                        </dl>
                    </div>
                @empty
                    <div class="enfas-empty py-4">
                        <strong>Nenhum medicamento adicionado</strong>
                        <div>Inclua ao menos um item antes de assinar.</div>
                    </div>
                @endforelse
            </div>
        </div>

        @if(!$prescription->isLocked())
        @can('prescriptions.manage')
        <div class="card">
            <div class="card-header">
                <strong class="d-block">Adicionar item</strong>
                <span class="small text-secondary">Preencha exatamente conforme a orientação clínica do profissional.</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('clinical-prescriptions.items.store',$prescription) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Medicamento *</label>
                            <input name="medication_name" class="form-control" maxlength="180" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Concentração</label>
                            <input name="concentration" class="form-control" maxlength="120" placeholder="Ex.: 500 mg">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Forma</label>
                            <input name="dosage_form" class="form-control" maxlength="120" placeholder="Ex.: comprimido">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Via</label>
                            <input name="route" class="form-control" maxlength="120" placeholder="Ex.: oral">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantidade</label>
                            <input name="quantity" class="form-control" maxlength="120" placeholder="Ex.: 1 caixa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duração</label>
                            <input name="duration" class="form-control" maxlength="120" placeholder="Ex.: 7 dias">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Posologia / modo de usar *</label>
                            <textarea name="directions" class="form-control" rows="3" maxlength="4000" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observações do item</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3"><i class="bi bi-plus-lg"></i>Adicionar item</button>
                </form>
            </div>
        </div>
        @endcan
        @endif
    </div>

    <div class="col-12 col-xl-4">
        <div class="card mb-4">
            <div class="card-header"><strong>Contexto clínico</strong></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="small text-secondary">Paciente</dt>
                    <dd>{{ $prescription->patient->displayName() }}{{ $prescription->patient->rgea_number ? ' · '.$prescription->patient->rgea_number : '' }}</dd>
                    <dt class="small text-secondary">Profissional</dt>
                    <dd>
                        {{ $prescription->professional->name }}
                        @if($prescription->professional->council_number)
                            <div class="small text-secondary">{{ $prescription->professional->council_type }} {{ $prescription->professional->council_number }}/{{ $prescription->professional->council_state }}</div>
                        @endif
                    </dd>
                    @if($prescription->appointment)
                    <dt class="small text-secondary">Atendimento</dt>
                    <dd>{{ $prescription->appointment->code }} · {{ $prescription->appointment->start_at->format('d/m/Y H:i') }}</dd>
                    @endif
                    <dt class="small text-secondary">Tipo</dt>
                    <dd>Receita simples</dd>
                </dl>
            </div>
        </div>

        @if(!$prescription->isLocked())
        @can('prescriptions.manage')
        <div class="card mb-4">
            <div class="card-header"><strong>Dados da prescrição</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('clinical-prescriptions.update',$prescription) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input name="title" value="{{ $prescription->title }}" class="form-control" maxlength="180" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orientações gerais</label>
                        <textarea name="notes" class="form-control" rows="5" maxlength="8000">{{ $prescription->notes }}</textarea>
                    </div>
                    <button class="btn btn-light border w-100"><i class="bi bi-save"></i>Salvar dados</button>
                </form>
            </div>
        </div>
        @endcan
        @endif

        <div class="card">
            <div class="card-header"><strong>Assinatura e integridade</strong></div>
            <div class="card-body">
                @if($prescription->status === 'signed')
                    <div class="alert alert-success">
                        <i class="bi bi-patch-check me-1"></i>
                        <strong>Prescrição assinada</strong>
                        <div class="small mt-1">{{ $prescription->signed_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="small text-secondary">Hash de integridade</div>
                    <code class="small text-break">{{ $prescription->content_hash }}</code>
                    <div class="small text-secondary mt-3">Provedor</div>
                    <div>{{ $prescription->external_provider ?: '—' }}</div>
                @else
                    <p class="small text-secondary">
                        Ao assinar, o conteúdo é transformado em documento clínico, recebe hash SHA-256 e fica bloqueado para alterações.
                    </p>
                    @can('prescriptions.sign')
                    <form method="POST" action="{{ route('clinical-prescriptions.sign',$prescription) }}" onsubmit="return confirm('Assinar e emitir esta prescrição? Depois disso ela ficará bloqueada.');">
                        @csrf
                        <button class="btn btn-primary w-100" @disabled($prescription->items->isEmpty())>
                            <i class="bi bi-pen"></i>Assinar e emitir
                        </button>
                    </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
