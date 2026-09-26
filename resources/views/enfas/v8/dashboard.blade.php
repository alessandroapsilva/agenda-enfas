@extends('enfas.layout')
@section('title','Central Premium')
@section('page_title','Central Premium')
@section('page_subtitle','Agenda, cadastros, WhatsApp e indicadores em uma única visão.')
@section('content')
<div class="row g-3 mb-4">
@foreach([
 ['Atendimentos hoje',$metrics['today'],'bi-calendar2-check'],
 ['Pacientes',$metrics['patients'],'bi-people'],
 ['Profissionais',$metrics['professionals'],'bi-person-badge'],
 ['Serviços',$metrics['services'],'bi-grid'],
 ['Templates ativos',$metrics['approved'],'bi-whatsapp'],
 ['Em análise',$metrics['pending'],'bi-hourglass-split']
] as $m)
<div class="col-xl-2 col-md-4 col-6"><div class="ea-v8-metric"><i class="bi {{ $m[2] }} fs-4"></i><div class="small text-secondary mt-3">{{ $m[0] }}</div><div class="fs-3 fw-semibold">{{ $m[1] }}</div></div></div>
@endforeach
</div>
<div class="row g-3">
@foreach([
 ['/agenda','Agenda','bi-calendar3'],
 ['/premium/profissionais','Profissionais','bi-person-badge'],
 ['/premium/pacientes','Pacientes','bi-people'],
 ['/premium/servicos','Serviços','bi-grid'],
 ['/premium/unidades','Unidades','bi-geo-alt'],
 ['/premium/disponibilidade','Disponibilidade','bi-calendar2-check'],
 ['/premium/whatsapp/modelos','Modelos WhatsApp','bi-whatsapp'],
 ['/premium/whatsapp/midia','Biblioteca de mídia','bi-images'],
 ['/premium/relatorios','Relatórios','bi-bar-chart'],
 ['/premium/auditoria','Auditoria','bi-shield-check']
] as $a)
<div class="col-xl-3 col-md-4 col-6"><a href="{{ url($a[0]) }}" class="ea-v8-launch"><i class="bi {{ $a[2] }}"></i><span>{{ $a[1] }}</span><i class="bi bi-chevron-right ms-auto"></i></a></div>
@endforeach
</div>
@stop
