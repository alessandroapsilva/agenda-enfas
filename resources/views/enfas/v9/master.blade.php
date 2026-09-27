@extends('enfas.layout')

@section('title',$config['title'])
@section('page_title',$config['title'])
@section('page_subtitle','Cadastro completo com inclusão, edição, ativação, desativação e exclusão controlada.')

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

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card v9-card sticky-xl-top" style="top:1rem">
            <div class="card-header border-0">
                <strong>
                    {{ $editing ? 'Editar '.$config['singular'] : 'Novo '.$config['singular'] }}
                </strong>
            </div>

            <form
                method="POST"
                action="{{ $editing
                    ? route('v9.'.$entity.'.update',$editing->id)
                    : route('v9.'.$entity.'.store') }}">
                @csrf
                @if($editing)
                    @method('PATCH')
                @endif

                <div class="card-body">
                    <div class="row g-3">
                        @foreach($config['fields'] as [$name,$label,$type,$required])
                            <div class="{{ $type==='textarea' ? 'col-12' : 'col-md-6 col-xl-12' }}">
                                <label class="form-label">
                                    {{ $label }}
                                </label>

                                @php($value=old($name,$editing->{$name}??''))

                                @if($type==='textarea')
                                    <textarea
                                        class="form-control"
                                        name="{{ $name }}"
                                        rows="4"
                                        @required($required)>{{ $value }}</textarea>
                                @elseif($type==='checkbox')
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <div class="form-check form-switch mt-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="{{ $name }}"
                                            value="1"
                                            @checked((bool)$value)>
                                        <label class="form-check-label">{{ $label }}</label>
                                    </div>
                                @else
                                    <input
                                        class="form-control"
                                        type="{{ $type }}"
                                        name="{{ $name }}"
                                        value="{{ $value }}"
                                        @if($name==='price') step="0.01" @endif
                                        @required($required)>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer bg-transparent d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1">
                        <i class="bi bi-check2 me-1"></i>
                        {{ $editing ? 'Salvar alterações' : 'Cadastrar' }}
                    </button>

                    @if($editing)
                        <a
                            href="{{ $config['path'] }}"
                            class="btn btn-light">
                            Cancelar
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card v9-card">
            <div class="card-header border-0">
                <form
                    method="GET"
                    class="d-flex gap-2 align-items-center">
                    <input
                        class="form-control"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Pesquisar por nome...">

                    <button class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>

                    @if(request('q'))
                        <a
                            href="{{ $config['path'] }}"
                            class="btn btn-light">
                            Limpar
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cadastro</th>
                            <th>Detalhes</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($rows as $row)
                        @php($active=(bool)($row->is_active??$row->active??true))

                        <tr>
                            <td>
                                <strong>{{ $row->name }}</strong>
                            </td>

                            <td>
                                <div class="small">
                                    {{ $row->phone??$row->email??$row->specialty??$row->description??'' }}
                                </div>

                                @if($entity === 'locations')
                                    <div class="small text-secondary">
                                        @if($row->code ?? null)
                                            <span class="badge text-bg-light border me-1">{{ $row->code }}</span>
                                        @endif
                                        @if($row->is_main ?? false)
                                            <span class="badge text-bg-primary">PRINCIPAL</span>
                                        @endif
                                    </div>
                                    @if(($row->address ?? null) || ($row->city ?? null))
                                        <div class="small text-secondary mt-1">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            {{ collect([
                                                trim(($row->address ?? '').(($row->address_number ?? null) ? ', '.$row->address_number : '')),
                                                $row->neighborhood ?? null,
                                                collect([$row->city ?? null,$row->state ?? null])->filter()->implode('/'),
                                            ])->filter()->implode(' · ') }}
                                        </div>
                                    @endif
                                @endif

                                @if(isset($row->registration_number) && $row->registration_number)
                                    <div class="small text-secondary">
                                        {{ $row->registration_type }}
                                        {{ $row->registration_number }}
                                    </div>
                                @endif

                                @if(isset($row->duration_minutes))
                                    <div class="small text-secondary">
                                        {{ $row->duration_minutes }} min
                                        @if(isset($row->price) && $row->price !== null)
                                            · R$ {{ number_format((float)$row->price,2,',','.') }}
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="badge text-bg-{{ $active ? 'success' : 'secondary' }}">
                                    {{ $active ? 'ATIVO' : 'INATIVO' }}
                                </span>
                            </td>

                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a
                                        href="{{ $config['path'].'?edit='.$row->id }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('v9.'.$entity.'.toggle',$row->id) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            class="btn btn-sm btn-outline-warning"
                                            title="{{ $active ? 'Desativar' : 'Ativar' }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('v9.'.$entity.'.delete',$row->id) }}"
                                        onsubmit="return confirm('Excluir definitivamente este cadastro? Se houver histórico o sistema vai bloquear a exclusão.')">
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
                                colspan="4"
                                class="text-center text-secondary py-5">
                                Nenhum registro encontrado.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@stop
