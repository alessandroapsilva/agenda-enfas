@extends('adminlte::page')

@push('adminlte_css')
    @vite('resources/css/enfas-agenda.css')
@endpush

@push('adminlte_js')
    @vite('resources/js/enfas-scan.js')
@endpush

@section('content_header')
    @hasSection('page_title')
        <div class="ea-pagehead">
            <div class="ea-pagehead-copy">
                @hasSection('page_kicker')
                    <div class="ea-page-kicker">@yield('page_kicker')</div>
                @endif

                <h1 class="ea-page-title">@yield('page_title')</h1>

                @hasSection('page_subtitle')
                    <p class="ea-page-subtitle">@yield('page_subtitle')</p>
                @endif
            </div>

            @hasSection('page_actions')
                <div class="ea-pagehead-actions">@yield('page_actions')</div>
            @endif
        </div>
    @endif
@stop
