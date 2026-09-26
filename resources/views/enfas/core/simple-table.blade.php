@extends('enfas.layout')
@section('title',$title)
@section('page_title',$title)
@section('page_subtitle',$subtitle)
@section('content')
@if(!$ready)<div class="alert alert-warning">Este recurso ainda não possui estrutura de dados nesta instalação.</div>
@else
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr>@foreach($columns as $label)<th>{{$label}}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($columns as $field=>$label)<td>@php $value=$row->{$field} ?? null; @endphp@if(is_bool($value)||$value===0||$value===1){{$value?'Sim':'Não'}}@else{{$value??'—'}}@endif</td>@endforeach</tr>
@empty<tr><td colspan="{{count($columns)}}"><div class="ea-empty"><i class="bi {{$icon}}"></i>Nenhum registro encontrado.</div></td></tr>@endforelse
</tbody></table></div></div>
@endif
@stop
