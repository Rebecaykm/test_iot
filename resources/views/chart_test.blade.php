@extends('adminlte::page')

@section('title', 'Mapa de Estaciones')

@section('content_header')
    <h1>Mapa de Estaciones</h1>
@stop

@section('content')
    @livewire('work-center-map')
@stop

@section('css')
    @stack('styles')
@stop

@section('js')
    @stack('scripts')
@stop
