@extends('layouts.app')

@section('title',$document->title)
@section('page_kicker','DOCUMENTO CLÍNICO')
@section('page_title',$document->title)
@section('page_subtitle','Paciente '.$document->patient->displayName().' · documento #'.$document->id)

@section('page_actions')
<a href="{{ route('clinical-documents.index',['patient_id'=>$document->patient_id,'appointment_id'=>$document->appointment_id]) }}" class="btn btn-light border">
    <i class="bi bi-arrow-left"></i>Documentos
</a>
<a href="{{ route('clinical-documents.print',$document) }}" target="_blank" class="btn btn-light border">
    <i class="bi bi-printer"></i>Visualizar / imprimir
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
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <strong class="d-block">Conteúdo</strong>
                    <span class="small text-secondary">Versão {{ $document->version }}</span>
                </div>
                <span class="badge text-bg-{{ $document->status==='signed' ? 'success':'light' }} {{ $document->status==='signed' ? '':'border' }}">
                    {{ $document->status==='signed' ? 'Assinado':'Rascunho' }}
                </span>
            </div>
            <div class="card-body">
                @if(!$document->isLocked())
                @can('documents.manage')
                <form method="POST" action="{{ route('clinical-documents.update',$document) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input name="title" value="{{ $document->title }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Conteúdo</label>
                        <textarea name="content" rows="22" class="form-control ea-document-editor" required>{{ $document->content }}</textarea>
                    </div>
                    <button class="btn btn-light border"><i class="bi bi-save"></i>Salvar rascunho</button>
                </form>
                @endcan
                @else
                <article class="ea-document-preview">{{ $document->content }}</article>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><strong>Contexto</strong></div>
            <div class="card-body">
                <dl class="ea-context-list">
                    <div><dt>Paciente</dt><dd>{{ $document->patient->displayName() }}</dd></div>
                    <div><dt>RGEA</dt><dd>{{ $document->patient->rgea_number }}</dd></div>
                    <div><dt>Profissional</dt><dd>{{ $document->professional?->name ?: '—' }}</dd></div>
                    <div><dt>Agendamento</dt><dd>{{ $document->appointment?->code ?: '—' }}</dd></div>
                    <div><dt>Criado</dt><dd>{{ $document->created_at?->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Assinatura</strong></div>
            <div class="card-body">
                @if($document->signatures->isNotEmpty())
                    @foreach($document->signatures as $signature)
                    <div class="ea-signature-card">
                        <div class="ea-signature-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <strong>{{ $signature->signer_name }}</strong>
                            @if($signature->signer_registry)<span>{{ $signature->signer_registry }}</span>@endif
                            <span>{{ $signature->signed_at?->format('d/m/Y H:i') }}</span>
                            <span>Assinatura eletrônica</span>
                        </div>
                    </div>
                    @endforeach
                @elseif(!$document->isLocked())
                    <p class="small text-secondary">Revise o documento antes de assinar. Depois da assinatura ele fica bloqueado para edição.</p>
                    @can('documents.sign')
                    <form method="POST" action="{{ route('clinical-documents.sign',$document) }}" onsubmit="return confirm('Assinar este documento? O conteúdo ficará bloqueado para edição.');">
                        @csrf
                        <button class="btn btn-primary w-100"><i class="bi bi-pen"></i>Assinar eletronicamente</button>
                    </form>
                    @endcan
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Integridade</strong></div>
            <div class="card-body">
                <div class="small text-secondary mb-2">SHA-256</div>
                <code class="ea-hash">{{ $document->content_hash ?: hash('sha256',$document->content) }}</code>
            </div>
        </div>
    </div>
</div>
@stop
