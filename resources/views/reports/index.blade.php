@extends('adminlte::page')

@section('title', 'Reportes Generales')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Reportes Generales</h1>
        </div>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body p-4">
            <div class="report-grid">

                {{-- Reporte MDI --}}
                <a href="{{ route('reports.mdi.export') }}" class="report-tile">
                    <span class="report-tile-icon">
                        {{-- Heroicon: code-bracket-square (outline) --}}
                        <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.25 9.75 16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                        </svg>
                    </span>
                    <span class="report-tile-text">Reporte MDI</span>
                    <span class="report-tile-badge">
                        <i class="fas fa-file-excel"></i> Excel
                    </span>
                </a>

            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .card, .btn, .form-control, .content-header h1 {
            font-family: 'Inter', sans-serif !important;
        }

        /* ── Cuadrícula de mosaicos ── */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        /* ── Mosaico (botón) ── */
        .report-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.75rem;
            padding: 2rem 1rem;
            min-height: 200px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #334155;
            position: relative;
        }
        .report-tile:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
            text-decoration: none;
        }
        .report-tile-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 88px;
            height: 88px;
            border-radius: 18px;
            background: #eff6ff;
            color: #1d4ed8;
        }
        .report-tile-icon .hi {
            width: 52px;
            height: 52px;
        }
        .report-tile-text {
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.25;
        }
        .report-tile-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #15803d;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 5px;
            padding: 0.12rem 0.5rem;
        }

        @media (max-width: 1199px) {
            .report-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 767px) {
            .report-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 479px) {
            .report-grid { grid-template-columns: 1fr; }
        }
    </style>
@stop
