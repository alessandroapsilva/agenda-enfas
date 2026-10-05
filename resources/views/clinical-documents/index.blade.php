@extends('layouts.app')

@section('title','Documentos clínicos')
@section('page_kicker','DOCUMENTOS')
@section('page_title','Documentos clínicos')
@section('page_subtitle','Receitas, atestados, declarações, encaminhamentos, solicitações e relatórios vinculados ao paciente.')

@section('page_actions')
@if($patient)
<a href="{{ route('patients.show',$patient) }}" class="btn btn-light border">
    <i class="bi bi-person-lines-fill"></i>Paciente
</a>
@endif
@can('documents.manage')
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newClinicalDocument">
    <i class="bi bi-file-earmark-plus"></i>Novo documento
</button>
@endcan
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
@endif

@if($patient)
<div class="ea-professional-head mb-4">
    <div class="d-flex align-items-center gap-3">
        <span class="ea-avatar">{{ strtoupper(substr($patient->displayName(),0,1)) }}</span>
        <div>
            <span class="small text-secondary">Paciente</span>
            <h5 class="mb-0">{{ $patient->displayName() }}</h5>
            <span class="small text-secondary">RGEA {{ $patient->rgea_number }}</span>
        </div>
    </div>
    @if($appointment)
    <div class="text-end">
        <span class="small text-secondary d-block">Agendamento</span>
        <strong>{{ $appointment->code }}</strong>
    </div>
    @endif
</div>
@endif

<div class="card">
    <div class="card-header">
        <div>
            <strong class="d-block">Documentos</strong>
            <span class="small text-secondary">Rascunhos podem ser editados. Documentos assinados ficam bloqueados.</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Paciente</th>
                    <th>Profissional</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            @forelse($documents as $document)
                <tr>
                    <td>
                        <strong>{{ $document->title }}</strong>
                        <div class="small text-secondary">{{ match($document->document_type) {
                            'prescription'=>'Receita',
                            'certificate'=>'Atestado',
                            'declaration'=>'Declaração',
                            'referral'=>'Encaminhamento',
                            'exam_request'=>'Solicitação de exame',
                            'report'=>'Relatório',
                            'orientation'=>'Orientações',
                            default=>'Documento',
                        } }}</div>
                    </td>
                    <td>{{ $document->patient?->displayName() }}</td>
                    <td>{{ $document->professional?->name ?: '—' }}</td>
                    <td>
                        <span class="badge text-bg-{{ $document->status==='signed' ? 'success' : 'light' }} {{ $document->status==='signed' ? '' : 'border' }}">
                            {{ $document->status==='signed' ? 'Assinado' : 'Rascunho' }}
                        </span>
                    </td>
                    <td>{{ ($document->signed_at ?: $document->created_at)?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('clinical-documents.show',$document) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye"></i>Abrir
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="enfas-empty py-5"><div class="enfas-empty-icon"><i class="bi bi-file-earmark-text"></i></div><h5>Nenhum documento</h5><p>Crie o primeiro documento clínico deste paciente.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($documents->hasPages())
    <div class="card-footer">{{ $documents->links() }}</div>
    @endif
</div>

@can('documents.manage')
<div class="modal fade" id="newClinicalDocument" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="{{ route('clinical-documents.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Novo documento clínico</h5>
                    <span class="small text-secondary">Crie o rascunho e assine depois da revisão.</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Paciente *</label>
                        @if($patient)
                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                            <input class="form-control" value="{{ $patient->displayName() }} · {{ $patient->rgea_number }}" disabled>
                        @else
                            <div class="alert alert-warning mb-0">Abra os documentos a partir da ficha do paciente para criar um novo registro.</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Agendamento</label>
                        <input type="hidden" name="appointment_id" value="{{ $appointment?->id }}">
                        <input class="form-control" value="{{ $appointment?->code ?: 'Sem agendamento vinculado' }}" disabled>
                    </div>
                    <input type="hidden" name="professional_id" value="{{ $appointment?->professional_id }}">

                    <div class="col-md-4">
                        <label class="form-label">Tipo *</label>
                        <select name="document_type" class="form-select" required>
                            <option value="prescription">Receita</option>
                            <option value="certificate">Atestado</option>
                            <option value="declaration">Declaração</option>
                            <option value="referral">Encaminhamento</option>
                            <option value="exam_request">Solicitação de exame</option>
                            <option value="report">Relatório</option>
                            <option value="orientation">Orientações</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Título *</label>
                        <input name="title" class="form-control" required placeholder="Ex.: Receita do atendimento">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Conteúdo *</label>
                        <textarea name="content" class="form-control ea-document-editor" rows="16" required placeholder="Digite o conteúdo do documento..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" @disabled(!$patient)><i class="bi bi-save"></i>Criar rascunho</button>
            </div>
        </form>
    </div>
</div>
@endcan
@stop
