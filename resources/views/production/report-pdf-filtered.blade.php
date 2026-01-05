<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Producción</title>
    <style>
        @page {
            size: letter landscape;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 10px;
            padding: 12px;
            color: #222;
        }

        .card-table {
            width: 100%;
            border: 0.5px solid #666;
            margin-bottom: 10px;
        }

        .card-table table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .card-table th,
        .card-table td {
            padding: 5px 6px;
            border: 0.5px solid #666;
            font-size: 8px;
            vertical-align: middle;
            text-align: center;
            font-family: Arial, Helvetica, sans-serif !important;
        }

        .inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inner-table td {
            border: none;
            padding: 2px 4px;
            font-size: 8px;
            vertical-align: middle;
            font-family: Arial, Helvetica, sans-serif !important;
        }

        .inner-table td:first-child {
            font-weight: 700;
            text-align: left;
            width: 70%;
        }

        .inner-table td:last-child {
            font-weight: 400;
            text-align: right;
            width: 30%;
            padding-right: 8px;
        }

        .code-cell {
            text-align: center !important;
            padding: 6px !important;
            font-weight: 700;
        }

        .header-title {
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .header-logo {
            width: 85px;
            display: block;
            margin: 0 auto;
        }

        .info-table {
            width: 100%;
            border: 0.5px solid #666;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .info-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 9px;
            text-align: left;
            padding: 6px 8px;
            border: 0.5px solid #666;
        }

        .info-table td {
            font-size: 9px;
            text-align: left;
            padding: 6px 8px;
            border: 0.5px solid #666;
        }

        .data-table {
            width: 100%;
            border: 0.5px solid #666;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .data-table thead th {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            padding: 8px 6px;
            border: 0.5px solid #666;
        }

        .data-table tbody td {
            font-size: 9px;
            text-align: center;
            padding: 7px 4px;
            border: 0.5px solid #666;
            font-weight: 400 !important;
        }

        .no-data-row {
            background-color: #f8f8f8;
            font-style: italic;
            color: #666;
            text-align: center;
            padding: 15px !important;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            margin: 15px 0 5px 0;
            background-color: #f0f0f0;
            padding: 5px;
            border-left: 3px solid #333;
        }

        .section-subtitle {
            font-weight: 600;
            font-size: 9px;
            margin: 10px 0 5px 0;
            color: #444;
            padding: 3px 0;
            border-bottom: 1px solid #ddd;
        }

        .page-break {
            page-break-after: always;
        }

        .no-break:last-child {
            page-break-after: auto;
        }

        .uppercase {
            text-transform: uppercase;
        }

        @media print {
            body {
                font-size: 10px;
            }

            .data-table td,
            .data-table th {
                font-size: 9px;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>

<body>
    @foreach ($groups as $group)
        @php
            $records = $group['records'] ?? collect();
            $line_name = $group['line_name'] ?? '-';
            $work_name = $group['work_name'] ?? '-';
            $shift_name = $group['shift_name'] ?? '-';
            $planned_date = $group['planned_date'] ?? '-';
            $shift_start_time = $group['shift_start_time'] ?? '-';
            $shift_end_time = $group['shift_end_time'] ?? '-';
            $work_number = $group['work_number'] ?? '-';
            $area_name = $group['area_name'] ?? 'MANUFACTURA';
            $scrap_records = $group['scrap_records'] ?? collect();
            $line_stoppage_records = $group['line_stoppage_records'] ?? collect();
        @endphp

        <div class="{{ $loop->last ? 'no-break' : 'page-break' }}">
            <!-- Header Principal (estructura simplificada y estable) -->
            <div class="card-table">
                <table>
                    <tbody>
                        <tr>
                            <td style="width: 18%; vertical-align: middle;">
                                <img src="{{ public_path('images/ykm.png') }}" alt="Logo YKM" class="header-logo">
                            </td>

                            <td style="width: 58%; vertical-align: middle;">
                                <div class="header-title">REPORTE DE PRODUCCIÓN IOT</div>
                            </td>

                            <td style="width: 24%; vertical-align: middle; text-align: left;">
                                <table class="inner-table" style="width:100%;">
                                    <tr>
                                        <td>REVISIÓN</td>
                                        <td style="text-align:right">1</td>
                                    </tr>
                                    <tr>
                                        <td>FECHA DE ELABORACIÓN</td>
                                        <td style="text-align:right">-</td>
                                    </tr>
                                    <tr>
                                        <td>ÚLTIMA REVISIÓN</td>
                                        <td style="text-align:right">-</td>
                                    </tr>
                                    <tr>
                                        <td>ÁREA</td>
                                        <td style="text-align:right" class="uppercase">{{ $area_name }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Información de Línea, Estación y Turno -->
            <table class="info-table">
                <tr>
                    <th style="width: 12%;">Línea</th>
                    <td style="width: 21%;">{{ $line_name }}</td>
                    <th style="width: 12%;">Estación</th>
                    <td style="width: 21%;">{{ $work_name }}</td>
                    <th style="width: 12%;">Lider</th>
                    <td style="width: 22%;"></td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td>
                        @if($planned_date && $planned_date !== '-')
                            {{ \Carbon\Carbon::parse($planned_date)->format('d/m/Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <th>Turno</th>
                    <td>{{ $shift_name }}</td>
                    <th>Hora de Inicio y Termino</th>
                    <td>
                        @if($shift_start_time && $shift_end_time && $shift_start_time !== '-' && $shift_end_time !== '-')
                            {{ \Carbon\Carbon::parse($shift_start_time)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($shift_end_time)->format('H:i') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>

            <!-- Tabla de Producción -->
            <div class="section-title">Registros de Producción</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Parte</th>
                        <th>No. Orden</th>
                        <th>Cant Planeada</th>
                        <th>C.T. / SPM</th>
                        <th>Tiempo Planeado</th>
                        <th>Hora Inicio</th>
                        <th>Hora Termino</th>
                        <th>Tiempo Total</th>
                        <th>Eficiencia</th>
                        <th>Cant. Producida</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($records->count() > 0)
                        @foreach ($records as $record)
                            @php
                                $startTime = $record->production_start
                                    ? \Carbon\Carbon::parse($record->production_start)
                                    : null;
                                $endTime = $record->production_end
                                    ? \Carbon\Carbon::parse($record->production_end)
                                    : null;
                                $totalMinutes = $startTime && $endTime ? $startTime->diffInMinutes($endTime) : 0;
                            @endphp
                            <tr>
                                <td>{{ $record->part_number }}</td>
                                <td>{{ $record->shop_order_number }}</td>
                                <td>{{ $record->planned_quantity }}</td>
                                <td>{{ is_numeric($record->calculated_cycletime) ? number_format($record->calculated_cycletime, 2) : '-' }}</td>
                                <td>{{ $record->calculated_planned_time ?? '-' }}</td>
                                <td>{{ $startTime ? $startTime->format('H:i') : '-' }}</td>
                                <td>{{ $endTime ? $endTime->format('H:i') : '-' }}</td>
                                <td>{{ number_format($totalMinutes, 2) }}</td>
                                <td>{{ is_numeric($record->calculated_efficiency) ? number_format($record->calculated_efficiency, 2) . '%' : '-' }}</td>
                                <td>{{ $record->produced_quantity }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="10" class="no-data-row">No se encontraron registros de producción para esta
                                estación, fecha y turno.</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <!-- Tabla de Scrap -->
            <div class="section-title">Registros de Scrap</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Scrap</th>
                        <th>No. Parte</th>
                        <th>Cantidad</th>
                        <th>Fecha/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($scrap_records->count() > 0)
                        @foreach ($scrap_records as $scrap)
                            <tr>
                                <td>{{ $scrap->scrap_code ?? '-' }}</td>
                                <td>{{ $scrap->scrap_name ?? '-' }}</td>
                                <td>{{ $scrap->part_number ?? '-' }}</td>
                                <td>{{ $scrap->quantity ?? '-' }}</td>
                                <td>{{ $scrap->created_at ? \Carbon\Carbon::parse($scrap->created_at)->format('d/m/Y H:i') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="no-data-row">No se encontraron registros de scrap para esta
                                estación, fecha y turno.</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <!-- Tabla de Paros de Línea -->
            <div class="section-title">Registros de Paros de Línea</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo de Paro</th>
                        <th>Paro</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Minutos</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($line_stoppage_records->count() > 0)
                        @foreach ($line_stoppage_records as $stoppage)
                            <tr>
                                <td>{{ $stoppage->type_line_stoppage_name ?? '-' }}</td>
                                <td>{{ $stoppage->line_stoppage_name ?? '-' }}</td>
                                <td>{{ $stoppage->start_time ? \Carbon\Carbon::parse($stoppage->start_time)->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td>{{ $stoppage->end_time ? \Carbon\Carbon::parse($stoppage->end_time)->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td>{{ $stoppage->minutes_stoppage ?? '0' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="no-data-row">No se encontraron registros de paros de línea para
                                esta estación, fecha y turno.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
