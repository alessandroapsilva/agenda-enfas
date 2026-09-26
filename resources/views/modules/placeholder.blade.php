@extends('layouts.app')

@section('title', $module)

@section('content_header')

<div>

    <h3 class="enfas-page-title">
        {{ $module }}
    </h3>

    <p class="enfas-page-subtitle">
        Módulo do ENFAS Agenda.
    </p>

</div>

@stop


@section('content')

<div class="card">

    <div class="card-body">

        <div class="enfas-empty">

            <div class="enfas-empty-icon">
                <i class="bi {{ $icon }}"></i>
            </div>

            <h5>
                {{ $module }}
            </h5>

            <p>
                Estrutura visual pronta.
                Este módulo será implementado na próxima etapa.
            </p>

        </div>

    </div>

</div>

@stop
