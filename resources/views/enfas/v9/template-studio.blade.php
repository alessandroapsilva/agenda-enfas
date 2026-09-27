@extends('enfas.layout')

@section('title','Modelos WhatsApp')
@section('page_title','Modelos WhatsApp')
@section('page_subtitle','Studio completo: incluir, editar revisões, duplicar, ativar, desativar, arquivar, excluir e usar imagens.')

@section('content')

@if(session('success'))
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>
    {{ $errors->first() }}
</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <strong>Modelos cadastrados</strong>
        <div class="small text-secondary">
            Modelos enviados à Meta preservam o histórico. Ao editar um deles, o Agenda cria automaticamente uma nova revisão local.
        </div>
    </div>

    <div class="d-flex gap-2">
        <a
            href="{{ url('/whatsapp/midia') }}"
            class="btn btn-outline-primary">
            <i class="bi bi-images me-1"></i>
            Biblioteca de mídia
        </a>

        <form
            method="POST"
            action="{{ route('v9.templates.sync') }}">
            @csrf

            <button class="btn btn-outline-primary">
                <i class="bi bi-arrow-repeat me-1"></i>
                Sincronizar Meta
            </button>
        </form>

        <a
            href="{{ url('/whatsapp/templates?novo=1') }}"
            class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Novo modelo
        </a>
    </div>
</div>

<div class="card v9-card mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Categoria</th>
                    <th>Status Meta</th>
                    <th>Uso Agenda</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>

            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row->name }}</strong>
                        <div class="small text-secondary">
                            {{ $row->purpose }}
                            · {{ $row->language }}
                            · revisão {{ $row->version??1 }}
                        </div>

                        <div
                            class="small text-secondary text-truncate"
                            style="max-width:480px">
                            {{ $row->body }}
                        </div>

                        @if($row->last_error)
                            <div class="small text-danger">
                                {{ \Illuminate\Support\Str::limit($row->last_error,150) }}
                            </div>
                        @endif
                    </td>

                    <td>
                        <span class="badge text-bg-info">
                            {{ $row->category }}
                        </span>

                        @if(($row->header_type??'TEXT')==='IMAGE')
                            <span class="badge text-bg-light">
                                <i class="bi bi-image"></i>
                                IMAGE
                            </span>
                        @endif
                    </td>

                    <td>
                        <span class="badge text-bg-{{
                            $row->status==='APPROVED'
                                ?'success'
                                :($row->status==='PENDING'
                                    ?'warning'
                                    :($row->status==='REJECTED'
                                        ?'danger'
                                        :'secondary'))
                        }}">
                            {{ $row->status }}
                        </span>
                    </td>

                    <td>
                        <span class="badge text-bg-{{ $row->is_active ? 'success' : 'secondary' }}">
                            {{ $row->is_active ? 'ATIVO' : 'DESATIVADO' }}
                        </span>
                    </td>

                    <td class="text-end">
                        <div class="d-inline-flex flex-wrap gap-1">
                            <a
                                href="{{ url('/whatsapp/templates?edit='.$row->id) }}"
                                class="btn btn-sm btn-outline-primary"
                                title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>

                            @if($row->status==='LOCAL' && blank($row->meta_template_id))
                                <form
                                    method="POST"
                                    action="{{ route('v9.templates.send',$row) }}">
                                    @csrf

                                    <button
                                        class="btn btn-sm btn-success"
                                        title="Enviar para Meta">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </form>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('v9.templates.duplicate',$row) }}">
                                @csrf

                                <button
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Duplicar">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('v9.templates.toggle',$row) }}">
                                @csrf
                                @method('PATCH')

                                <button
                                    class="btn btn-sm btn-outline-warning"
                                    title="{{ $row->is_active ? 'Desativar' : 'Ativar' }}">
                                    <i class="bi bi-power"></i>
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('v9.templates.archive',$row) }}">
                                @csrf

                                <button
                                    class="btn btn-sm btn-outline-dark"
                                    title="Arquivar">
                                    <i class="bi bi-archive"></i>
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('v9.templates.delete',$row) }}"
                                onsubmit="return confirm('Excluir este modelo? Se ele estiver na Meta, o Agenda também tentará removê-lo da Meta.')">
                                @csrf
                                @method('DELETE')

                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="5"
                        class="text-center text-secondary py-5">
                        Nenhum modelo cadastrado.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(request('novo') || $editing)
@php
    $edit=$editing;
    $buttonsText=$edit
        ?collect($edit->buttons??[])
            ->map(fn($b)=>($b['text']??'').'|'.($b['action']??'none'))
            ->implode("\n")
        :'';
@endphp

<div class="card v9-card">
    <div class="card-header border-0">
        <strong>
            {{ $edit ? 'Editar modelo / criar revisão' : 'Novo modelo' }}
        </strong>

        @if($edit && (filled($edit->meta_template_id) || $edit->status!=='LOCAL'))
            <div class="small text-warning mt-1">
                Este modelo já possui histórico na Meta. Ao salvar, o Agenda criará uma nova revisão LOCAL e preservará o original.
            </div>
        @endif
    </div>

    <form
        method="POST"
        action="{{ $edit
            ?route('v9.templates.update',$edit)
            :route('v9.templates.store') }}">
        @csrf
        @if($edit)
            @method('PATCH')
        @endif

        <div class="card-body">
            <div class="row g-3">
                @if(!$edit)
                    <div class="col-md-4">
                        <label class="form-label">Nome técnico</label>
                        <input
                            class="form-control"
                            name="name"
                            placeholder="lembrete_agendamento"
                            required>
                    </div>
                @else
                    <div class="col-md-4">
                        <label class="form-label">Modelo original</label>
                        <input
                            class="form-control"
                            value="{{ $edit->name }}"
                            disabled>
                    </div>
                @endif

                <div class="col-md-3">
                    <label class="form-label">Finalidade</label>
                    <select
                        class="form-select"
                        name="purpose"
                        required>
                        @foreach([
                            'confirmation'=>'Confirmação',
                            'reminder'=>'Lembrete',
                            'reschedule'=>'Reagendamento',
                            'cancellation'=>'Cancelamento',
                            'post_service'=>'Pós-atendimento',
                            'return'=>'Retorno',
                            'medication_pickup'=>'Retirada de medicamento',
                            'general'=>'Geral',
                        ] as $value=>$label)
                            <option
                                value="{{ $value }}"
                                @selected(($edit->purpose??'reminder')===$value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Categoria Meta</label>
                    <select
                        class="form-select"
                        name="category"
                        required>
                        @foreach(['UTILITY','MARKETING','AUTHENTICATION'] as $cat)
                            <option
                                value="{{ $cat }}"
                                @selected(($edit->category??'UTILITY')===$cat)>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Idioma</label>
                    <input
                        class="form-control"
                        name="language"
                        value="{{ $edit->language??'pt_BR' }}"
                        required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo do cabeçalho</label>
                    <select
                        class="form-select"
                        name="header_type">
                        <option value="NONE">Sem cabeçalho</option>
                        <option
                            value="TEXT"
                            @selected(($edit->header_type??'TEXT')==='TEXT')>
                            Texto
                        </option>
                        <option
                            value="IMAGE"
                            @selected(($edit->header_type??'')==='IMAGE')>
                            Imagem
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Texto do cabeçalho</label>
                    <input
                        class="form-control"
                        name="header_text"
                        value="{{ $edit->header_text??'' }}">
                </div>

                <div class="col-md-5">
                    <label class="form-label">Imagem</label>
                    <select
                        class="form-select"
                        name="header_media_id">
                        <option value="">Nenhuma</option>

                        @foreach($media as $item)
                            <option
                                value="{{ $item->id }}"
                                @selected((string)($edit->header_media_id??'')===(string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Mensagem</label>
                    <textarea
                        class="form-control"
                        name="body"
                        rows="10"
                        maxlength="1024"
                        required>{{ $edit->body??'' }}</textarea>

                    <small class="text-secondary">
                        Você pode editar o texto livremente. Variáveis aceitas ficam no formato {{ '{' }}{1}{{ '}' }}, {{ '{' }}{2}{{ '}' }}, etc.
                    </small>
                    <div class="small text-secondary mt-2">
                        Chaves disponíveis: <code>paciente_nome</code>, <code>data</code>, <code>hora</code>,
                        <code>profissional</code>, <code>servico</code>, <code>codigo_agendamento</code>,
                        <code>local</code>, <code>rgea</code>, <code>medicamento</code>,
                        <code>quantidade_medicamento</code>, <code>retorno_data</code> e <code>jornada_url</code>.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Variáveis em ordem</label>
                    <input
                        class="form-control"
                        name="variable_keys_text"
                        value="{{ $edit ? implode(',',$edit->variable_keys??[]) : '' }}"
                        placeholder="paciente_nome,servico,data,hora,profissional,jornada_url">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Exemplos em ordem</label>
                    <input
                        class="form-control"
                        name="sample_values_text"
                        value="{{ $edit ? implode(',',$edit->sample_values??[]) : '' }}"
                        placeholder="João,Consulta,31/08/2026,14:30,Dra. Maria">
                </div>

                <div class="col-12">
                    <label class="form-label">Rodapé</label>
                    <input
                        class="form-control"
                        name="footer"
                        value="{{ $edit->footer??'ENFAS Agenda' }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Botões rápidos</label>
                    <textarea
                        class="form-control"
                        name="buttons_text"
                        rows="3"
                        placeholder="Confirmar|confirm&#10;Reagendar|reschedule&#10;Cancelar|cancel">{{ $buttonsText }}</textarea>

                    <small class="text-secondary">
                        Até 3 botões. O texto antes de | é exibido para o paciente; a ação depois de | é interna do Agenda.
                    </small>
                </div>
            </div>
        </div>

        <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
            <a
                href="{{ url('/whatsapp/templates') }}"
                class="btn btn-light">
                Cancelar
            </a>

            <button
                name="action"
                value="draft"
                class="btn btn-outline-primary">
                <i class="bi bi-save me-1"></i>
                Salvar
            </button>

            @if(!$edit)
                <button
                    name="action"
                    value="send"
                    class="btn btn-success">
                    <i class="bi bi-send-check me-1"></i>
                    Salvar e enviar à Meta
                </button>
            @endif
        </div>
    </form>
</div>
@endif
@stop
