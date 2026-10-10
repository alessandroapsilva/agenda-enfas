@extends('enfas.layout')

@section('title','Modelos de mensagem')
@section('page_kicker','Comunicação')
@section('page_title','Modelos de mensagem')
@section(
    'page_subtitle',
    'Crie, edite e publique mensagens do WhatsApp com identidade visual e histórico de versões.'
)

@section('page_actions')
<a
    href="{{ route('v9.media.index') }}"
    class="ea-btn-secondary"
>
    <i class="bi bi-images"></i>
    Mídia
</a>

<a
    href="{{ url('/whatsapp/templates?novo=1') }}"
    class="ea-btn-main"
>
    <i class="bi bi-plus-lg"></i>
    Novo modelo
</a>
@endsection

@section('content')

@php
    $purposeLabels = [
        'confirmation' => 'Confirmação',
        'reminder' => 'Lembrete',
        'reschedule' => 'Reagendamento',
        'cancellation' => 'Cancelamento',
        'post_service' => 'Pós-atendimento',
        'return' => 'Retorno',
        'medication_pickup' => 'Retirada de medicamento',
        'general' => 'Comunicação geral',
    ];

    $statusLabels = [
        'APPROVED' => ['Aprovado', 'confirmed'],
        'PENDING' => ['Em análise', 'waiting'],
        'REJECTED' => ['Rejeitado', 'failed'],
        'LOCAL' => ['Rascunho', 'neutral'],
        'MISSING' => ['Ausente na Meta', 'failed'],
    ];

    $edit = $editing;

    $buttonsText = $edit
        ? collect($edit->buttons ?? [])
            ->map(
                fn ($button) =>
                    ($button['text'] ?? '')
                    .'|'
                    .($button['action'] ?? 'none')
            )
            ->implode("\n")
        : '';

    $initialHeaderType = old(
        'header_type',
        $edit->header_type
            ?? ($brandLogo ? 'IMAGE' : 'TEXT')
    );

    $initialMediaId = old(
        'header_media_id',
        $edit->header_media_id
            ?? $brandLogo?->id
    );
@endphp

@if(session('success'))
    <div class="ea-flash">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ $errors->first() }}
    </div>
@endif

<div class="ea-template-overview mb-3">
    <div class="ea-template-overview-copy">
        <span class="ea-section-kicker">
            Biblioteca operacional
        </span>

        <h2>
            {{ $rows->count() }} modelo(s) cadastrado(s)
        </h2>

        <p>
            Modelos aprovados pela Meta são preservados. Ao editar um aprovado,
            o Agenda cria uma nova revisão para você alterar com segurança.
        </p>
    </div>

    <div class="ea-template-brand-state">
        <span class="ea-template-brand-icon">
            @if($brandLogo)
                <img
                    src="{{ asset('storage/'.$brandLogo->path) }}"
                    alt="Logo {{ $brandName }}"
                >
            @else
                <i class="bi bi-image"></i>
            @endif
        </span>

        <div>
            <strong>{{ $brandName }}</strong>
            <span>
                {{
                    $brandLogo
                        ? 'Logo disponível para cabeçalhos'
                        : 'Cadastre um logo PNG/JPG/WebP em Aparência'
                }}
            </span>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('v9.templates.sync') }}"
    >
        @csrf

        <button
            class="ea-btn-secondary"
            type="submit"
        >
            <i class="bi bi-arrow-repeat"></i>
            Sincronizar Meta
        </button>
    </form>
</div>

<section class="ea-saas-card mb-3">
    <div class="ea-saas-card-head">
        <div>
            <span class="ea-section-kicker">
                Modelos ativos e revisões
            </span>

            <h2>Biblioteca de mensagens</h2>

            <p>
                Edite conteúdo, identidade, variáveis e botões sem perder o histórico já aprovado.
            </p>
        </div>
    </div>

    @if($rows->isEmpty())
        <div class="ea-premium-empty">
            <span class="ea-premium-empty-icon">
                <i class="bi bi-chat-square-text"></i>
            </span>

            <strong>Nenhum modelo cadastrado</strong>

            <p>
                Crie o primeiro modelo para começar a automatizar confirmações e lembretes.
            </p>

            <a
                href="{{ url('/whatsapp/templates?novo=1') }}"
                class="ea-btn-main"
            >
                Criar modelo
            </a>
        </div>
    @else
        <div class="ea-template-list">
            @foreach($rows as $row)
                @php
                    $statusUi =
                        $statusLabels[$row->status]
                        ?? [$row->status, 'neutral'];

                    $purposeLabel =
                        $purposeLabels[$row->purpose]
                        ?? ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $row->purpose
                            )
                        );

                    $withLogo =
                        ($row->header_type ?? 'NONE')
                        === 'IMAGE';
                @endphp

                <article class="ea-template-row">
                    <div class="ea-template-row-main">
                        <div class="ea-template-row-icon {{
                            $withLogo
                                ? 'is-branded'
                                : ''
                        }}">
                            <i class="bi {{
                                $withLogo
                                    ? 'bi-image'
                                    : 'bi-chat-square-text'
                            }}"></i>
                        </div>

                        <div>
                            <strong>{{ $row->name }}</strong>

                            <span>
                                {{ $purposeLabel }}
                                · {{ $row->language }}
                                · versão {{ $row->version ?? 1 }}
                            </span>

                            <p>
                                {{
                                    IlluminateSupportStr::limit(
                                        trim($row->body),
                                        140
                                    )
                                }}
                            </p>

                            @if($row->last_error)
                                <small class="ea-template-error">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    {{
                                        IlluminateSupportStr::limit(
                                            $row->last_error,
                                            150
                                        )
                                    }}
                                </small>
                            @endif
                        </div>
                    </div>

                    <div class="ea-template-row-meta">
                        <span class="ea-status {{ $statusUi[1] }}">
                            {{ $statusUi[0] }}
                        </span>

                        <span class="ea-template-category">
                            {{ $row->category }}
                        </span>

                        @if($withLogo)
                            <span class="ea-template-brand-pill">
                                <i class="bi bi-image"></i>
                                Com imagem
                            </span>
                        @endif

                        <span class="ea-status {{
                            $row->is_active
                                ? 'confirmed'
                                : 'neutral'
                        }}">
                            {{
                                $row->is_active
                                    ? 'Em uso'
                                    : 'Pausado'
                            }}
                        </span>
                    </div>

                    <div class="ea-template-row-actions">
                        <a
                            href="{{ url('/whatsapp/templates?edit='.$row->id) }}"
                            class="ea-btn-secondary"
                        >
                            <i class="bi bi-pencil"></i>
                            Editar
                        </a>

                        @if($brandLogo && ! $withLogo)
                            <form
                                method="POST"
                                action="{{ route('v9.templates.brand',$row) }}"
                            >
                                @csrf

                                <button
                                    class="ea-btn-secondary"
                                    type="submit"
                                    title="Criar uma nova revisão usando o logo oficial"
                                >
                                    <i class="bi bi-stars"></i>
                                    Versão com logo
                                </button>
                            </form>
                        @endif

                        @if($row->status === 'LOCAL' && blank($row->meta_template_id))
                            <form
                                method="POST"
                                action="{{ route('v9.templates.send',$row) }}"
                            >
                                @csrf

                                <button
                                    class="ea-btn-main"
                                    type="submit"
                                >
                                    <i class="bi bi-send-check"></i>
                                    Enviar à Meta
                                </button>
                            </form>
                        @endif

                        <div class="dropdown">
                            <button
                                class="ea-icon-action"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                title="Mais ações"
                            >
                                <i class="bi bi-three-dots"></i>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end">
                                <form
                                    method="POST"
                                    action="{{ route('v9.templates.duplicate',$row) }}"
                                >
                                    @csrf

                                    <button
                                        class="dropdown-item"
                                        type="submit"
                                    >
                                        <i class="bi bi-copy me-2"></i>
                                        Duplicar
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('v9.templates.toggle',$row) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="dropdown-item"
                                        type="submit"
                                    >
                                        <i class="bi bi-power me-2"></i>
                                        {{
                                            $row->is_active
                                                ? 'Pausar uso'
                                                : 'Ativar uso'
                                        }}
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('v9.templates.archive',$row) }}"
                                >
                                    @csrf

                                    <button
                                        class="dropdown-item"
                                        type="submit"
                                    >
                                        <i class="bi bi-archive me-2"></i>
                                        Arquivar
                                    </button>
                                </form>

                                <div class="dropdown-divider"></div>

                                <form
                                    method="POST"
                                    action="{{ route('v9.templates.delete',$row) }}"
                                    onsubmit="return confirm('Excluir este modelo?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="dropdown-item text-danger"
                                        type="submit"
                                    >
                                        <i class="bi bi-trash me-2"></i>
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@if(request('novo') || $editing)
    <section class="ea-template-editor">
        <div class="ea-template-editor-main">
            <form
                id="templateEditorForm"
                method="POST"
                action="{{
                    $edit
                        ? route('v9.templates.update',$edit)
                        : route('v9.templates.store')
                }}"
            >
                @csrf

                @if($edit)
                    @method('PATCH')
                @endif

                <div class="ea-saas-card">
                    <div class="ea-saas-card-head">
                        <div>
                            <span class="ea-section-kicker">
                                {{
                                    $edit
                                        ? 'Edição de modelo'
                                        : 'Novo modelo'
                                }}
                            </span>

                            <h2>
                                {{
                                    $edit
                                        ? $edit->name
                                        : 'Criar mensagem'
                                }}
                            </h2>

                            <p>
                                @if($edit && (filled($edit->meta_template_id) || $edit->status !== 'LOCAL'))
                                    O modelo atual já tem histórico na Meta. Ao salvar, uma nova revisão editável será criada.
                                @else
                                    Edite o conteúdo livremente antes de publicar.
                                @endif
                            </p>
                        </div>

                        @if($brandLogo)
                            <button
                                id="useOfficialLogo"
                                type="button"
                                class="ea-btn-secondary"
                            >
                                <i class="bi bi-image"></i>
                                Usar logo oficial
                            </button>
                        @endif
                    </div>

                    <div class="ea-template-editor-body">
                        <div class="ea-template-form-section">
                            <div class="ea-template-form-section-head">
                                <span>1</span>

                                <div>
                                    <strong>Identificação</strong>
                                    <small>Finalidade e configuração técnica do modelo.</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                @if(!$edit)
                                    <div class="col-lg-5">
                                        <label class="form-label">
                                            Nome técnico
                                        </label>

                                        <input
                                            class="form-control"
                                            name="name"
                                            value="{{ old('name') }}"
                                            placeholder="lembrete_agendamento"
                                            required
                                        >

                                        <div class="form-text">
                                            Letras minúsculas, números e underscore são recomendados pela Meta.
                                        </div>
                                    </div>
                                @else
                                    <div class="col-lg-5">
                                        <label class="form-label">
                                            Modelo de origem
                                        </label>

                                        <input
                                            class="form-control"
                                            value="{{ $edit->name }}"
                                            disabled
                                        >
                                    </div>
                                @endif

                                <div class="col-lg-3">
                                    <label class="form-label">
                                        Finalidade
                                    </label>

                                    <select
                                        class="form-select"
                                        name="purpose"
                                        required
                                    >
                                        @foreach($purposeLabels as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected(
                                                    old(
                                                        'purpose',
                                                        $edit->purpose ?? 'reminder'
                                                    ) === $value
                                                )
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2">
                                    <label class="form-label">
                                        Categoria Meta
                                    </label>

                                    <select
                                        class="form-select"
                                        name="category"
                                        required
                                    >
                                        @foreach(['UTILITY','MARKETING','AUTHENTICATION'] as $category)
                                            <option
                                                value="{{ $category }}"
                                                @selected(
                                                    old(
                                                        'category',
                                                        $edit->category ?? 'UTILITY'
                                                    ) === $category
                                                )
                                            >
                                                {{ $category }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2">
                                    <label class="form-label">
                                        Idioma
                                    </label>

                                    <input
                                        class="form-control"
                                        name="language"
                                        value="{{
                                            old(
                                                'language',
                                                $edit->language ?? 'pt_BR'
                                            )
                                        }}"
                                        required
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="ea-template-form-section">
                            <div class="ea-template-form-section-head">
                                <span>2</span>

                                <div>
                                    <strong>Identidade da mensagem</strong>
                                    <small>Imagem ou texto exibido no topo do WhatsApp.</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label class="form-label">
                                        Cabeçalho
                                    </label>

                                    <select
                                        id="templateHeaderType"
                                        class="form-select"
                                        name="header_type"
                                        required
                                    >
                                        <option
                                            value="NONE"
                                            @selected($initialHeaderType === 'NONE')
                                        >
                                            Sem cabeçalho
                                        </option>

                                        <option
                                            value="TEXT"
                                            @selected($initialHeaderType === 'TEXT')
                                        >
                                            Texto
                                        </option>

                                        <option
                                            value="IMAGE"
                                            @selected($initialHeaderType === 'IMAGE')
                                        >
                                            Imagem / logo
                                        </option>
                                    </select>
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">
                                        Imagem
                                    </label>

                                    <select
                                        id="templateMedia"
                                        class="form-select"
                                        name="header_media_id"
                                    >
                                        <option
                                            value=""
                                            data-url=""
                                        >
                                            Nenhuma
                                        </option>

                                        @foreach($media as $item)
                                            <option
                                                value="{{ $item->id }}"
                                                data-url="{{ asset('storage/'.$item->path) }}"
                                                @selected(
                                                    (string) $initialMediaId
                                                    === (string) $item->id
                                                )
                                            >
                                                {{ $item->name }}
                                                {{
                                                    $brandLogo
                                                    && (int) $brandLogo->id === (int) $item->id
                                                        ? ' · logo oficial'
                                                        : ''
                                                }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">
                                        Texto do cabeçalho
                                    </label>

                                    <input
                                        id="templateHeaderText"
                                        class="form-control"
                                        name="header_text"
                                        value="{{
                                            old(
                                                'header_text',
                                                $edit->header_text ?? ''
                                            )
                                        }}"
                                        placeholder="Confirmação de horário"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="ea-template-form-section">
                            <div class="ea-template-form-section-head">
                                <span>3</span>

                                <div>
                                    <strong>Conteúdo</strong>
                                    <small>Texto que o paciente realmente recebe.</small>
                                </div>
                            </div>

                            <label class="form-label">
                                Mensagem
                            </label>

                            <textarea
                                id="templateBody"
                                class="form-control ea-template-body"
                                name="body"
                                rows="8"
                                maxlength="1024"
                                required
                            >{{ old('body',$edit->body ?? '') }}</textarea>

                            <div class="ea-template-token-list">
                                @foreach([
                                    'paciente_nome' => 'Paciente',
                                    'data' => 'Data',
                                    'hora' => 'Hora',
                                    'servico' => 'Serviço',
                                    'profissional' => 'Profissional',
                                    'local' => 'Local',
                                    'codigo_agendamento' => 'Código',
                                    'jornada_url' => 'Link do agendamento',
                                ] as $key => $label)
                                    <span>
                                        <code>{{ $key }}</code>
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-lg-6">
                                    <label class="form-label">
                                        Variáveis em ordem
                                    </label>

                                    <input
                                        id="templateKeys"
                                        class="form-control"
                                        name="variable_keys_text"
                                        value="{{
                                            old(
                                                'variable_keys_text',
                                                $edit
                                                    ? implode(',',$edit->variable_keys ?? [])
                                                    : ''
                                            )
                                        }}"
                                        placeholder="paciente_nome,servico,data,hora"
                                    >
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">
                                        Exemplos em ordem
                                    </label>

                                    <input
                                        id="templateSamples"
                                        class="form-control"
                                        name="sample_values_text"
                                        value="{{
                                            old(
                                                'sample_values_text',
                                                $edit
                                                    ? implode(',',$edit->sample_values ?? [])
                                                    : ''
                                            )
                                        }}"
                                        placeholder="João,Fisioterapia,15/10/2026,14:30"
                                    >
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">
                                        Rodapé
                                    </label>

                                    <input
                                        id="templateFooter"
                                        class="form-control"
                                        name="footer"
                                        value="{{
                                            old(
                                                'footer',
                                                $edit->footer
                                                    ?? $brandName
                                            )
                                        }}"
                                    >
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">
                                        Botões rápidos
                                    </label>

                                    <textarea
                                        id="templateButtons"
                                        class="form-control"
                                        name="buttons_text"
                                        rows="3"
                                        placeholder="Confirmar|confirm&#10;Reagendar|reschedule&#10;Cancelar|cancel"
                                    >{{ old('buttons_text',$buttonsText) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ea-template-editor-footer">
                        <a
                            href="{{ route('v9.templates.index') }}"
                            class="ea-btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            name="action"
                            value="draft"
                            class="ea-btn-secondary"
                            type="submit"
                        >
                            <i class="bi bi-save"></i>
                            {{
                                $edit
                                    ? (
                                        filled($edit->meta_template_id)
                                        || $edit->status !== 'LOCAL'
                                            ? 'Criar revisão'
                                            : 'Salvar alterações'
                                    )
                                    : 'Salvar rascunho'
                            }}
                        </button>

                        @if(!$edit)
                            <button
                                name="action"
                                value="send"
                                class="ea-btn-main"
                                type="submit"
                            >
                                <i class="bi bi-send-check"></i>
                                Salvar e enviar à Meta
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <aside class="ea-template-preview-card">
            <div class="ea-template-preview-head">
                <span class="ea-section-kicker">
                    Prévia
                </span>

                <strong>Como o paciente verá</strong>

                <small>
                    A Meta pode ajustar espaçamentos finais após a aprovação.
                </small>
            </div>

            <div class="ea-wa-preview-phone">
                <div class="ea-wa-preview-topbar">
                    <span class="ea-wa-preview-avatar">
                        @if($brandLogo)
                            <img
                                src="{{ asset('storage/'.$brandLogo->path) }}"
                                alt=""
                            >
                        @else
                            <i class="bi bi-heart-pulse"></i>
                        @endif
                    </span>

                    <div>
                        <strong>{{ $brandName }}</strong>
                        <span>conta comercial</span>
                    </div>
                </div>

                <div class="ea-wa-preview-chat">
                    <div class="ea-wa-preview-bubble">
                        <img
                            id="templatePreviewImage"
                            class="ea-wa-preview-image d-none"
                            src=""
                            alt="Cabeçalho"
                        >

                        <strong
                            id="templatePreviewHeader"
                            class="ea-wa-preview-header"
                        ></strong>

                        <div
                            id="templatePreviewBody"
                            class="ea-wa-preview-body"
                        ></div>

                        <small
                            id="templatePreviewFooter"
                            class="ea-wa-preview-footer"
                        ></small>

                        <span class="ea-wa-preview-time">
                            10:32
                            <i class="bi bi-check2-all"></i>
                        </span>
                    </div>

                    <div
                        id="templatePreviewButtons"
                        class="ea-wa-preview-buttons"
                    ></div>
                </div>
            </div>

            @if($brandLogo)
                <div class="ea-template-preview-brand">
                    <i class="bi bi-patch-check"></i>

                    <span>
                        Logo oficial disponível. Novos modelos podem usar imagem no cabeçalho.
                    </span>
                </div>
            @endif
        </aside>
    </section>
@endif

@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const headerType =
        document.getElementById('templateHeaderType');

    const media =
        document.getElementById('templateMedia');

    const headerText =
        document.getElementById('templateHeaderText');

    const body =
        document.getElementById('templateBody');

    const keys =
        document.getElementById('templateKeys');

    const samples =
        document.getElementById('templateSamples');

    const footer =
        document.getElementById('templateFooter');

    const buttons =
        document.getElementById('templateButtons');

    const previewImage =
        document.getElementById('templatePreviewImage');

    const previewHeader =
        document.getElementById('templatePreviewHeader');

    const previewBody =
        document.getElementById('templatePreviewBody');

    const previewFooter =
        document.getElementById('templatePreviewFooter');

    const previewButtons =
        document.getElementById('templatePreviewButtons');

    if (! body) {
        return;
    }

    const values = (field) =>
        String(field?.value || '')
            .split(',')
            .map(item => item.trim())
            .filter(Boolean);

    const renderBody = () => {
        let text = String(body.value || '');

        const sampleValues =
            values(samples);

        sampleValues.forEach(
            (sample, index) => {
                text = text
                    .split('{{' + (index + 1) + '}}')
                    .join(sample);
            }
        );

        return text;
    };

    const renderButtons = () => {
        previewButtons.innerHTML = '';

        String(buttons?.value || '')
            .split('\n')
            .map(item => item.trim())
            .filter(Boolean)
            .slice(0, 3)
            .forEach((line) => {
                const label =
                    line.split('|')[0].trim();

                if (! label) {
                    return;
                }

                const node =
                    document.createElement('div');

                node.className =
                    'ea-wa-preview-button';

                node.textContent =
                    label;

                previewButtons.appendChild(
                    node
                );
            });
    };

    const refresh = () => {
        const type =
            headerType?.value || 'NONE';

        const selected =
            media?.selectedOptions?.[0];

        const imageUrl =
            selected?.dataset?.url || '';

        previewImage.classList.toggle(
            'd-none',
            type !== 'IMAGE'
            || ! imageUrl
        );

        if (
            type === 'IMAGE'
            && imageUrl
        ) {
            previewImage.src =
                imageUrl;
        }

        previewHeader.classList.toggle(
            'd-none',
            type !== 'TEXT'
            || ! headerText?.value
        );

        previewHeader.textContent =
            type === 'TEXT'
                ? headerText?.value || ''
                : '';

        previewBody.textContent =
            renderBody();

        previewFooter.textContent =
            footer?.value || '';

        renderButtons();
    };

    [
        headerType,
        media,
        headerText,
        body,
        keys,
        samples,
        footer,
        buttons
    ].forEach((field) => {
        field?.addEventListener(
            'input',
            refresh
        );

        field?.addEventListener(
            'change',
            refresh
        );
    });

    document.getElementById(
        'useOfficialLogo'
    )?.addEventListener(
        'click',
        () => {
            const brandId =
                @json($brandLogo?->id);

            if (! brandId) {
                return;
            }

            headerType.value =
                'IMAGE';

            media.value =
                String(brandId);

            headerText.value =
                '';

            refresh();
        }
    );

    refresh();
});
</script>
@endsection
