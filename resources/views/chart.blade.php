@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    @livewire('work-center-overview')
@stop

@section('css')
    @stack('styles')
@stop

@section('js')
    @stack('scripts')
@stop
