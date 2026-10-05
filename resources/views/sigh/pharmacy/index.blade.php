@extends('enfas.layout')

@section('title','Assistência Farmacêutica')
@section('page_kicker','SIGH ENFAS')
@section('page_title','Assistência Farmacêutica')
@section('page_subtitle','Medicamentos, PMC, LME e APAC integrados ao prontuário administrativo do paciente.')

@section('content')
@if(session('success'))
<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <strong>Não foi possível salvar.</strong>
    <div class="mt-1">{{ $errors->first() }}</div>
</div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['Medicamentos ativos',$stats['active_medications'],'bi-capsule','Acompanhamentos farmacológicos ativos'],
        ['PMC em atenção',$stats['pmc_attention'],'bi-house-heart','Previsão de término em até 7 dias'],
        ['LME pendentes',$stats['lme_attention'],'bi-file-earmark-medical','Solicitações em andamento'],
        ['APAC em atenção',$stats['apac_attention'],'bi-file-earmark-check','Validade ausente ou até 30 dias'],
    ] as $metric)
    <div class="col-md-6 col-xl-3">
        <div class="enfas-stat h-100">
            <div class="d-flex align-items-center justify-content-between">
                <span class="enfas-stat-label">{{ $metric[0] }}</span>
                <span class="enfas-stat-icon d-grid place-items-center"><i class="bi {{ $metric[2] }}"></i></span>
            </div>
            <div class="enfas-stat-number">{{ $metric[1] }}</div>
            <div class="enfas-stat-foot">{{ $metric[3] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mb-4">
    <div class="card-header">
        <div>
            <strong class="d-block">Localizar paciente</strong>
            <span class="small text-secondary">Busque por nome, RGEA ou CPF antes de abrir o acompanhamento.</span>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('sigh.pharmacy.index') }}" class="row g-2 align-items-end">
            <div class="col-lg-8">
                <label class="form-label">Paciente</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input class="form-control" name="q" value="{{ $q }}" placeholder="Nome, RGEA ou CPF">
                </div>
            </div>
            <div class="col-lg-2 d-grid">
                <button class="btn btn-primary"><i class="bi bi-search"></i>Buscar</button>
            </div>
            <div class="col-lg-2 d-grid">
                <a class="btn btn-light border" href="{{ route('sigh.pharmacy.index') }}">Limpar</a>
            </div>
        </form>

        @if($patients->isNotEmpty())
        <div class="mt-3 d-flex flex-wrap gap-2">
            @foreach($patients as $patient)
                <a class="btn btn-light border {{ $selectedPatient?->id === $patient->id ? 'active' : '' }}"
                   href="{{ route('sigh.pharmacy.index',['patient_id'=>$patient->id]) }}">
                    <i class="bi bi-person"></i>
                    {{ $patient->displayName() }}
                    <span class="text-secondary">· {{ $patient->rgea_number }}</span>
                </a>
            @endforeach
        </div>
        @endif
    </div>
</div>

@if($selectedPatient)
<div class="ea-professional-head mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="ea-avatar">{{ strtoupper(substr($selectedPatient->displayName(),0,1)) }}</div>
        <div>
            <span class="small text-secondary d-block">Paciente selecionado</span>
            <h4 class="mb-0">{{ $selectedPatient->displayName() }}</h4>
            <span class="small text-secondary">RGEA {{ $selectedPatient->rgea_number }} @if($selectedPatient->birth_date) · {{ $selectedPatient->birth_date->format('d/m/Y') }} @endif</span>
        </div>
    </div>
    <a href="{{ route('patients.show',$selectedPatient) }}" class="btn btn-light border">
        <i class="bi bi-person-lines-fill"></i>Ficha do paciente
    </a>
</div>

<ul class="nav nav-pills sigh-care-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#medications"><i class="bi bi-capsule me-1"></i>Medicamentos</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pmc"><i class="bi bi-house-heart me-1"></i>PMC</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#lme"><i class="bi bi-file-earmark-medical me-1"></i>LME</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#apac"><i class="bi bi-file-earmark-check me-1"></i>APAC</button></li>
<li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#documents"><i class="bi bi-folder2-open me-1"></i>Documentos</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="medications">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <strong>Medicamentos do paciente</strong>
                    <div class="small text-secondary">Registro administrativo da terapia informada/prescrita.</div>
                </div>
                @can('pharmacy.manage')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medicationModal"><i class="bi bi-plus-lg"></i>Adicionar</button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Medicamento</th><th>Posologia</th><th>Prescritor</th><th>Situação</th></tr></thead>
                    <tbody>
                    @forelse($medications as $row)
                        <tr>
                            <td>
    <strong>{{ $row->medication_name }}</strong>
    @if($row->requires_special_control)
        <div class="mt-1"><span class="badge text-bg-warning"><i class="bi bi-shield-exclamation me-1"></i>Controle especial</span></div>
    @endif
</td>
                            <td>{{ collect([$row->dosage,$row->route,$row->frequency])->filter()->implode(' · ') ?: '—' }}</td>
                            <td>{{ $row->prescriber_name ?: '—' }} @if($row->prescriber_registry)<div class="small text-secondary">{{ $row->prescriber_registry }}</div>@endif</td>
                            <td><span class="badge text-bg-{{ $row->is_active ? 'success':'secondary' }}">{{ $row->is_active ? 'Ativo':'Encerrado' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="enfas-empty py-4"><strong>Nenhum medicamento cadastrado</strong><div>Inclua os medicamentos acompanhados para este paciente.</div></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pmc">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <strong>PMC · Medicamento em Casa</strong>
                    <div class="small text-secondary">Saldo domiciliar, consumo estimado e próxima reposição.</div>
                </div>
                @can('pharmacy.manage')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pmcModal"><i class="bi bi-plus-lg"></i>Registrar PMC</button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Medicamento</th><th>Saldo em casa</th><th>Término estimado</th><th>Próxima reposição</th></tr></thead>
                    <tbody>
                    @forelse($pmc as $row)
                        <tr>
                            <td>{{ $row->medication_name ?: 'Não vinculado' }}</td>
                            <td>{{ $row->quantity_at_home !== null ? rtrim(rtrim(number_format($row->quantity_at_home,3,',','.'),'0'),',').' '.($row->unit ?: '') : '—' }}</td>
                            <td>{{ $row->estimated_end_at ? IlluminateSupportCarbon::parse($row->estimated_end_at)->format('d/m/Y') : '—' }}</td>
                            <td>{{ $row->next_supply_at ? IlluminateSupportCarbon::parse($row->next_supply_at)->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="enfas-empty py-4"><strong>Nenhum PMC cadastrado</strong><div>Registre quando houver acompanhamento de medicamento em domicílio.</div></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="lme">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <strong>LME</strong>
                    <div class="small text-secondary">Solicitação, protocolo, análise, validade e renovação.</div>
                </div>
                @can('pharmacy.manage')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lmeModal"><i class="bi bi-plus-lg"></i>Nova LME</button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Medicamento</th><th>CID</th><th>Protocolo</th><th>Status</th><th>Renovação</th></tr></thead>
                    <tbody>
                    @forelse($lmes as $row)
                        <tr>
                            <td><strong>{{ $row->medication_name }}</strong></td>
                            <td>{{ $row->cid10 ?: '—' }}</td>
                            <td>{{ $row->protocol_number ?: '—' }}</td>
                            <td><span class="badge text-bg-light border">{{ str_replace('_',' ',mb_strtoupper($row->status)) }}</span></td>
                            <td>{{ $row->renewal_due_at ? IlluminateSupportCarbon::parse($row->renewal_due_at)->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="enfas-empty py-4"><strong>Nenhuma LME em acompanhamento</strong></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="apac">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <strong>APAC</strong>
                    <div class="small text-secondary">Autorizações ambulatoriais vinculadas ao paciente.</div>
                </div>
                @can('pharmacy.manage')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#apacModal"><i class="bi bi-plus-lg"></i>Nova APAC</button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Procedimento</th><th>Código</th><th>Autorização</th><th>Competência</th><th>Validade</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($apacs as $row)
                        <tr>
                            <td><strong>{{ $row->procedure_name }}</strong></td>
                            <td>{{ $row->procedure_code ?: '—' }}</td>
                            <td>{{ $row->authorization_number ?: '—' }}</td>
                            <td>{{ $row->competence ?: '—' }}</td>
                            <td>{{ $row->authorized_until ? IlluminateSupportCarbon::parse($row->authorized_until)->format('d/m/Y') : '—' }}</td>
                            <td><span class="badge text-bg-light border">{{ mb_strtoupper($row->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="enfas-empty py-4"><strong>Nenhuma APAC em acompanhamento</strong></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="tab-pane fade" id="documents">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <strong>Documentos do paciente</strong>
                    <div class="small text-secondary">Receitas, LME, APAC, laudos, exames e autorizações em armazenamento privado.</div>
                </div>
                @can('pharmacy.manage')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#documentModal"><i class="bi bi-upload"></i>Anexar documento</button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Documento</th><th>Categoria</th><th>Data</th><th>Validade</th><th class="text-end">Ação</th></tr></thead>
                    <tbody>
                    @forelse($documents as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->title }}</strong>
                                <div class="small text-secondary">{{ $row->original_name }}</div>
                            </td>
                            <td>{{ mb_strtoupper($row->category) }}</td>
                            <td>{{ $row->document_date ? IlluminateSupportCarbon::parse($row->document_date)->format('d/m/Y') : '—' }}</td>
                            <td>{{ $row->valid_until ? IlluminateSupportCarbon::parse($row->valid_until)->format('d/m/Y') : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('sigh.pharmacy.documents.download',$row->id) }}" class="btn btn-sm btn-light border">
                                    <i class="bi bi-download"></i>Baixar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="enfas-empty py-4"><strong>Nenhum documento anexado</strong><div>Os arquivos ficam armazenados de forma privada e exigem autenticação para download.</div></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@can('pharmacy.manage')
<div class="modal fade" id="medicationModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('sigh.pharmacy.medications.store') }}" class="modal-content">@csrf
<input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
<div class="modal-header"><div><h5 class="modal-title">Adicionar medicamento</h5><small class="text-secondary">{{ $selectedPatient->displayName() }}</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
<div class="col-12"><label class="form-label">Medicamento *</label><input name="medication_name" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Dose</label><input name="dosage" class="form-control" placeholder="Ex.: 20 mg"></div>
<div class="col-md-4"><label class="form-label">Via</label><input name="route" class="form-control" placeholder="Ex.: oral"></div>
<div class="col-md-4"><label class="form-label">Frequência</label><input name="frequency" class="form-control" placeholder="Ex.: 1x ao dia"></div>
<div class="col-md-6"><label class="form-label">Prescritor</label><input name="prescriber_name" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Registro profissional</label><input name="prescriber_registry" class="form-control"></div>
<div class="col-12">
    <label class="form-check">
        <input type="hidden" name="requires_special_control" value="0">
        <input class="form-check-input" type="checkbox" name="requires_special_control" value="1">
        <span class="form-check-label">Medicamento sujeito a controle especial</span>
    </label>
</div>
<div class="col-md-3"><label class="form-label">Categoria de controle</label><input name="control_category" class="form-control" placeholder="Ex.: categoria interna"></div>
<div class="col-md-3"><label class="form-label">Tipo de receituário</label><input name="prescription_type" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Nº receituário</label><input name="prescription_number" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Validade do receituário</label><input type="date" name="prescription_valid_until" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Início</label><input type="date" name="started_at" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Término</label><input type="date" name="ended_at" class="form-control"></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar medicamento</button></div>
</form></div></div>

<div class="modal fade" id="pmcModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('sigh.pharmacy.pmc.store') }}" class="modal-content">@csrf
<input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
<div class="modal-header"><div><h5 class="modal-title">Registrar PMC</h5><small class="text-secondary">Controle de medicamento em casa</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
<div class="col-12"><label class="form-label">Medicamento acompanhado</label><select name="patient_medication_id" class="form-select"><option value="">Não vincular</option>@foreach($medications as $row)<option value="{{ $row->id }}">{{ $row->medication_name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Quantidade em casa</label><input type="number" step="0.001" min="0" name="quantity_at_home" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Consumo diário</label><input type="number" step="0.001" min="0" name="daily_consumption" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Unidade</label><input name="unit" class="form-control" placeholder="comprimidos, mL..."></div>
<div class="col-md-4"><label class="form-label">Última entrega</label><input type="date" name="last_delivery_at" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Término estimado</label><input type="date" name="estimated_end_at" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Próxima reposição</label><input type="date" name="next_supply_at" class="form-control"></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar PMC</button></div>
</form></div></div>

<div class="modal fade" id="lmeModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><form method="POST" action="{{ route('sigh.pharmacy.lme.store') }}" class="modal-content">@csrf
<input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
<div class="modal-header"><div><h5 class="modal-title">Nova LME</h5><small class="text-secondary">Controle administrativo da solicitação e renovação</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Medicamento *</label><input name="medication_name" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">CID-10</label><input name="cid10" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Status *</label><select name="status" class="form-select" required><option value="draft">Rascunho</option><option value="pending_documents">Documentação pendente</option><option value="submitted">Protocolado</option><option value="under_review">Em análise</option><option value="approved">Deferido</option><option value="denied">Indeferido</option><option value="dispensing">Em dispensação</option><option value="renewal_due">Renovação</option><option value="closed">Encerrado</option></select></div>
<div class="col-12"><label class="form-label">Diagnóstico</label><input name="diagnosis" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Prescritor</label><input name="prescriber_name" class="form-control"></div>
<div class="col-md-2"><label class="form-label">Registro</label><input name="prescriber_registry" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Data da solicitação</label><input type="date" name="requested_at" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Protocolo</label><input name="protocol_number" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Validade</label><input type="date" name="valid_until" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Renovação prevista</label><input type="date" name="renewal_due_at" class="form-control"></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar LME</button></div>
</form></div></div>

<div class="modal fade" id="apacModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><form method="POST" action="{{ route('sigh.pharmacy.apac.store') }}" class="modal-content">@csrf
<input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
<div class="modal-header"><div><h5 class="modal-title">Nova APAC</h5><small class="text-secondary">Autorização de procedimento ambulatorial</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
<div class="col-md-3"><label class="form-label">Código</label><input name="procedure_code" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Procedimento *</label><input name="procedure_name" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">CID-10</label><input name="cid10" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Nº autorização</label><input name="authorization_number" class="form-control"></div>
<div class="col-md-2"><label class="form-label">Competência</label><input name="competence" class="form-control" placeholder="2026-10"></div>
<div class="col-md-3"><label class="form-label">Início</label><input type="date" name="authorized_from" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Fim</label><input type="date" name="authorized_until" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Estabelecimento</label><input name="establishment" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Profissional</label><input name="professional_name" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Status *</label><select name="status" class="form-select"><option value="draft">Rascunho</option><option value="pending">Pendente</option><option value="active">Ativa</option><option value="expired">Vencida</option><option value="closed">Encerrada</option><option value="denied">Indeferida</option></select></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar APAC</button></div>
</form></div></div>


<div class="modal fade" id="documentModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('sigh.pharmacy.documents.store') }}" enctype="multipart/form-data" class="modal-content">@csrf
<input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
<div class="modal-header"><div><h5 class="modal-title">Anexar documento</h5><small class="text-secondary">PDF, JPG ou PNG · até 15 MB</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
<div class="col-md-4"><label class="form-label">Categoria *</label><select name="category" class="form-select" required><option value="prescription">Receita</option><option value="lme">LME</option><option value="apac">APAC</option><option value="exam">Exame</option><option value="report">Laudo/Relatório</option><option value="authorization">Autorização</option><option value="identity">Documento pessoal</option><option value="other">Outro</option></select></div>
<div class="col-md-8"><label class="form-label">Título *</label><input name="title" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Data do documento</label><input type="date" name="document_date" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Validade</label><input type="date" name="valid_until" class="form-control"></div>
<div class="col-12"><label class="form-label">Arquivo *</label><input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i class="bi bi-shield-lock"></i>Salvar documento</button></div>
</form></div></div>

@endcan

@else
<div class="card">
    <div class="card-body py-5">
        <div class="enfas-empty">
            <div class="enfas-empty-icon"><i class="bi bi-person-search"></i></div>
            <h5>Selecione um paciente</h5>
            <p>O acompanhamento de medicamentos, PMC, LME e APAC aparece aqui.</p>
        </div>
    </div>
</div>
@endif
@stop
