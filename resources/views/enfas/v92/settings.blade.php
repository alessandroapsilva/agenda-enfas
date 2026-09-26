@extends('enfas.layout')
@section('title','Configurações')
@section('page_title','Configurações')
@section('page_subtitle','Preferências operacionais da agenda e comunicação.')

@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card v92-card">
    <form method="POST" action="{{ route('v92.settings.save') }}">
        @csrf

        <div class="card-body row g-4">
            <div class="col-md-6">
                <label class="form-label">Nome da organização</label>
                <input class="form-control" name="company_name" value="{{ $settings['company_name']??'Enfermagem Alessandro Silva' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Lembrete padrão (minutos)</label>
                <input class="form-control" type="number" name="default_reminder_minutes" value="{{ $settings['default_reminder_minutes']??1440 }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Intervalo padrão (minutos)</label>
                <input class="form-control" type="number" name="appointment_interval" value="{{ $settings['appointment_interval']??30 }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Início do expediente</label>
                <input class="form-control" type="time" name="business_hours_start" value="{{ $settings['business_hours_start']??'08:00' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Fim do expediente</label>
                <input class="form-control" type="time" name="business_hours_end" value="{{ $settings['business_hours_end']??'18:00' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Rodapé padrão do WhatsApp</label>
                <input class="form-control" name="whatsapp_footer" value="{{ $settings['whatsapp_footer']??'ENFAS Agenda' }}">
            </div>
        </div>

        <div class="card-footer bg-transparent">
            <button class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i>
                Salvar configurações
            </button>
        </div>
    </form>
</div>
@stop
