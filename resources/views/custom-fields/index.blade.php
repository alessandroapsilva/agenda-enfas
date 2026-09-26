@extends('layouts.app')

@section('title', 'Campos personalizados')

@section('content_header')

<div>

    <h3 class="enfas-page-title">
        Campos personalizados
    </h3>

    <p class="enfas-page-subtitle">
        Adapte o formulário de agendamento sem alterar código.
    </p>

</div>

@stop


@section('content')

<div class="row g-3">

    <div class="col-xl-4">

        <div class="card">

            <div class="card-header">

                <h6 class="mb-0 fw-semibold">
                    Novo campo
                </h6>

            </div>

            <form
                method="POST"
                action="{{ route('custom-fields.store') }}"
                class="card-body">

                @csrf

                <div class="mb-3">

                    <label class="form-label">
                        Nome do campo
                    </label>

                    <input
                        name="name"
                        class="form-control"
                        required>

                </div>

                <div class="row g-3">

                    <div class="col-6">

                        <label class="form-label">
                            Onde aparece
                        </label>

                        <select
                            name="entity_type"
                            class="form-select">

                            <option value="appointment">
                                Agendamento
                            </option>

                            <option value="patient">
                                Paciente
                            </option>

                        </select>

                    </div>

                    <div class="col-6">

                        <label class="form-label">
                            Tipo
                        </label>

                        <select
                            name="field_type"
                            class="form-select">

                            <option value="text">Texto</option>
                            <option value="textarea">Texto longo</option>
                            <option value="number">Número</option>
                            <option value="date">Data</option>
                            <option value="select">Lista</option>
                            <option value="checkbox">Sim / Não</option>

                        </select>

                    </div>

                </div>

                <div class="mt-3">

                    <label class="form-label">
                        Placeholder
                    </label>

                    <input
                        name="placeholder"
                        class="form-control">

                </div>

                <div class="mt-3">

                    <label class="form-label">
                        Opções
                    </label>

                    <textarea
                        name="options_text"
                        rows="4"
                        class="form-control"
                        placeholder="Uma opção por linha">
                    </textarea>

                    <small class="text-secondary">
                        Usado em campos do tipo Lista.
                    </small>

                </div>

                <div class="mt-3">

                    <label class="form-label">
                        Ajuda
                    </label>

                    <textarea
                        name="help_text"
                        rows="2"
                        class="form-control">
                    </textarea>

                </div>

                <div class="row g-3 mt-0">

                    <div class="col-5">

                        <label class="form-label">
                            Ordem
                        </label>

                        <input
                            type="number"
                            name="sort_order"
                            value="0"
                            min="0"
                            class="form-control">

                    </div>

                    <div class="col-7 d-flex align-items-end">

                        <div class="form-check mb-2">

                            <input
                                type="checkbox"
                                name="is_required"
                                value="1"
                                class="form-check-input"
                                id="requiredField">

                            <label
                                for="requiredField"
                                class="form-check-label">

                                Obrigatório

                            </label>

                        </div>

                    </div>

                </div>

                <button
                    class="btn btn-primary w-100 mt-4">

                    Criar campo

                </button>

            </form>

        </div>

    </div>


    <div class="col-xl-8">

        <div class="card">

            <div class="card-header">

                <h6 class="mb-0 fw-semibold">
                    Campos configurados
                </h6>

            </div>

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead>
                    <tr>
                        <th>Campo</th>
                        <th>Local</th>
                        <th>Tipo</th>
                        <th>Obrigatório</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>

                    <tbody>

                    @forelse($fields as $field)

                        <tr>

                            <td>
                                <strong>{{ $field->name }}</strong>
                                <div class="small text-secondary">
                                    {{ $field->slug }}
                                </div>
                            </td>

                            <td>
                                {{ $field->entity_type === 'appointment'
                                    ? 'Agendamento'
                                    : 'Paciente'
                                }}
                            </td>

                            <td>
                                {{ $field->field_type }}
                            </td>

                            <td>
                                {{ $field->is_required ? 'Sim' : 'Não' }}
                            </td>

                            <td>

                                <span class="badge text-bg-{{ $field->is_active ? 'success' : 'secondary' }}">

                                    {{ $field->is_active ? 'Ativo' : 'Inativo' }}

                                </span>

                            </td>

                            <td class="text-end">

                                <form
                                    method="POST"
                                    action="{{ route('custom-fields.status', $field) }}">

                                    @csrf
                                    @method('PATCH')

                                    <button class="btn btn-sm btn-light">

                                        {{ $field->is_active
                                            ? 'Desativar'
                                            : 'Ativar'
                                        }}

                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6"
                                class="text-center py-5 text-secondary">

                                Nenhum campo personalizado.

                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@stop
