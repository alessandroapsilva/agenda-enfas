@extends('enfas.layout')
@section('title','Configurações Premium')
@section('page_title','Configurações Premium')
@section('page_subtitle','Preferências gerais da operação e do WhatsApp.')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card ea-v8-card"><form method="POST" action="{{ route('v8.settings.save') }}">@csrf<div class="card-body row g-4"><div class="col-md-6"><label class="form-label">Nome da organização</label><input class="form-control" name="company_name" value="{{ $settings['company_name']??'Enfermagem Alessandro Silva' }}"></div><div class="col-md-3"><label class="form-label">Lembrete padrão (min)</label><input class="form-control" type="number" name="default_reminder_minutes" value="{{ $settings['default_reminder_minutes']??1440 }}"></div><div class="col-md-3"><label class="form-label">Intervalo padrão (min)</label><input class="form-control" type="number" name="appointment_interval" value="{{ $settings['appointment_interval']??30 }}"></div><div class="col-12"><label class="form-label">Rodapé do WhatsApp</label><input class="form-control" name="whatsapp_footer" value="{{ $settings['whatsapp_footer']??'ENFAS Agenda' }}"></div></div><div class="card-footer bg-transparent"><button class="btn btn-primary">Salvar configurações</button></div></form></div>
@stop
