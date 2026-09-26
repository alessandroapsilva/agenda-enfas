@extends('enfas.layout')
@section('title','Modelos WhatsApp Premium')
@section('page_title','Modelos WhatsApp Premium')
@section('page_subtitle','Edite rascunhos, desative modelos, duplique versões, corrija textos e use imagens.')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="d-flex justify-content-between align-items-center mb-3"><div><strong>Modelos operacionais</strong><div class="small text-secondary">Somente APPROVED + ATIVO deve entrar em novas automações.</div></div><div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ url('/premium/whatsapp/midia') }}"><i class="bi bi-images me-1"></i>Mídia</a><a class="btn btn-primary" href="{{ url('/whatsapp/templates') }}"><i class="bi bi-plus-lg me-1"></i>Novo modelo</a></div></div>
<div class="card ea-v8-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Modelo</th><th>Status Meta</th><th>Uso</th><th>Cabeçalho</th><th class="text-end">Ações</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td><strong>{{ $r->name }}</strong><div class="small text-secondary">{{ $r->purpose }} · {{ $r->language }} · {{ $r->category }}</div><div class="small text-secondary text-truncate" style="max-width:430px">{{ $r->body }}</div>@if($r->last_error)<div class="small text-danger">{{ \Illuminate\Support\Str::limit($r->last_error,120) }}</div>@endif</td><td><span class="badge text-bg-{{ $r->status==='APPROVED'?'success':($r->status==='PENDING'?'warning':($r->status==='REJECTED'?'danger':'secondary')) }}">{{ $r->status }}</span></td><td><span class="badge text-bg-{{ $r->is_active?'success':'secondary' }}">{{ $r->is_active?'ATIVO':'DESATIVADO' }}</span></td><td>{{ $r->header_type??'TEXT' }} @if($r->header_media_id)<i class="bi bi-image"></i>@endif</td><td class="text-end"><div class="d-inline-flex flex-wrap gap-1">
@if(in_array($r->status,['LOCAL','REJECTED']))<a class="btn btn-sm btn-outline-primary" href="{{ url('/premium/whatsapp/modelos?edit='.$r->id) }}"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('v8.templates.autofix',$r) }}">@csrf<button class="btn btn-sm btn-outline-info" title="Corrigir texto"><i class="bi bi-magic"></i></button></form><form method="POST" action="{{ route('v8.templates.send',$r) }}">@csrf<button class="btn btn-sm btn-success" title="Enviar para Meta"><i class="bi bi-send"></i></button></form>@endif
@if($r->status==='REJECTED')
<form method="POST" action="{{ route('v8.templates.recover-category',$r) }}">
@csrf
<button class="btn btn-sm btn-outline-success" title="Recriar com categoria adequada">
<i class="bi bi-arrow-counterclockwise"></i>
</button>
</form>
@endif
<form method="POST" action="{{ route('v8.templates.toggle',$r) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning"><i class="bi bi-power"></i></button></form>
<form method="POST" action="{{ route('v8.templates.duplicate',$r) }}">@csrf<button class="btn btn-sm btn-outline-secondary"><i class="bi bi-copy"></i></button></form>
<form method="POST" action="{{ route('v8.templates.archive',$r) }}">@csrf<button class="btn btn-sm btn-outline-dark"><i class="bi bi-archive"></i></button></form>
</div></td></tr>@endforeach
</tbody></table></div></div>

@if($editing)
<div class="modal fade" id="editTemplate" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ route('v8.templates.update',$editing) }}">@csrf @method('PATCH')
<div class="modal-header"><div><h5 class="modal-title">Editar {{ $editing->name }}</h5><div class="small text-secondary">Para modelos já aprovados, duplique primeiro e edite a nova versão.</div></div><a href="{{ url('/premium/whatsapp/modelos') }}" class="btn-close"></a></div>
<div class="modal-body"><div class="row g-3">
<div class="col-md-3"><label class="form-label">Finalidade</label><input class="form-control" name="purpose" value="{{ $editing->purpose }}"></div><div class="col-md-3"><label class="form-label">Categoria</label><select class="form-select" name="category"><option @selected($editing->category==='UTILITY')>UTILITY</option><option @selected($editing->category==='MARKETING')>MARKETING</option><option @selected($editing->category==='AUTHENTICATION')>AUTHENTICATION</option></select></div>
<div class="col-md-3"><label class="form-label">Cabeçalho</label><select class="form-select" name="header_type" id="headerType"><option value="NONE">Sem cabeçalho</option><option value="TEXT" @selected(($editing->header_type??'TEXT')==='TEXT')>Texto</option><option value="IMAGE" @selected(($editing->header_type??'')==='IMAGE')>Imagem</option></select></div>
<div class="col-md-3"><label class="form-label">Imagem</label><select class="form-select" name="header_media_id"><option value="">Nenhuma</option>@foreach($media as $m)<option value="{{ $m->id }}" @selected((string)$editing->header_media_id===(string)$m->id)>{{ $m->name }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Texto do cabeçalho</label><input class="form-control" name="header_text" value="{{ $editing->header_text }}"></div>
<div class="col-12"><label class="form-label">Mensagem</label><textarea class="form-control" name="body" rows="10" maxlength="1024" required>{{ $editing->body }}</textarea></div>
<div class="col-12"><label class="form-label">Rodapé</label><input class="form-control" name="footer" value="{{ $editing->footer }}"></div>
<div class="col-md-6"><label class="form-label">Variáveis</label><input class="form-control" name="variable_keys_text" value="{{ implode(',',$editing->variable_keys??[]) }}"></div>
<div class="col-md-6"><label class="form-label">Exemplos</label><input class="form-control" name="sample_values_text" value="{{ implode(',',$editing->sample_values??[]) }}"></div>
<div class="col-12"><label class="form-label">Botões</label><textarea class="form-control" name="buttons_text" rows="3">{{ collect($editing->buttons??[])->map(fn($b)=>($b['text']??'').'|'.($b['action']??'none'))->implode("\n") }}</textarea></div>
</div></div><div class="modal-footer"><a class="btn btn-light" href="{{ url('/premium/whatsapp/modelos') }}">Cancelar</a><button class="btn btn-primary">Salvar alterações</button></div></form></div></div>
@endif
@stop
@section('js')
@if($editing)<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('editTemplate')).show());</script>@endif
@stop
