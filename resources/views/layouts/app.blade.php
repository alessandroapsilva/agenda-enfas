@extends('adminlte::page')

@push('adminlte_css')
<link
    rel="icon"
    href="{{ asset(config('enfas.favicon_path','assets/brand/enfas-agenda.svg')) }}"
>
<link
    rel="stylesheet"
    href="{{ asset('assets/enfas/enfas-v11.css') }}?v=11.1"
>
@endpush

@section('content_header')
    @hasSection('page_title')
        <div class="ea-pagehead">
            <div class="ea-pagehead-copy">
                @hasSection('page_kicker')
                    <div class="ea-page-kicker">
                        @yield('page_kicker')
                    </div>
                @endif

                <h1 class="ea-page-title">
                    @yield('page_title')
                </h1>

                @hasSection('page_subtitle')
                    <p class="ea-page-subtitle">
                        @yield('page_subtitle')
                    </p>
                @endif
            </div>

            @hasSection('page_actions')
                <div class="ea-pagehead-actions">
                    @yield('page_actions')
                </div>
            @endif
        </div>
    @endif
@stop

@push('js')
<script
    src="{{ asset('assets/enfas/enfas-v11.js') }}?v=11.1"
></script>
@endpush

{{-- ENFAS_SAAS_V11_2 --}}
@push('adminlte_css')
<link
    rel="stylesheet"
    href="{{ asset('assets/enfas/enfas-saas.css') }}?v=11.2">
@endpush

@push('js')
<script
    src="{{ asset('assets/enfas/enfas-saas.js') }}?v=11.2"></script>
@endpush
