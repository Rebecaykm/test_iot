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

        .page-break {
            page-break-after: always;
        }

        .no-break:last-child {
            page-break-after: auto;
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
            $records = $group['records'];
            $line_name = $group['line_name'];
            $work_name = $group['work_name'];
            $shift_name = $group['shift_name'];
            $planned_date = $group['planned_date'];
            $shift_start_time = $group['shift_start_time'];
            $shift_end_time = $group['shift_end_time'];
            $work_number = $group['work_number'];
            $area_name = $group['area_name'] ?? 'MANUFACTURA'; // Asumiendo que viene del controlador
        @endphp

        <div class="{{ $loop->last ? 'no-break' : 'page-break' }}">
            <!-- Header Principal -->
            <div class="card-table">
                <table>
                    <tbody>
                        <tr>
                            <td rowspan="3" style="width: 20%;">
                                <img src="{{ public_path('images/ykm.png') }}" alt="Logo YKM" class="header-logo">
                            </td>

                            <td colspan="4" rowspan="4" class="header-title"
                                style="vertical-align: middle; text-align: center;">
                                REPORTE DE PRODUCCIÓN IoT
                            </td>

                            <td>
                                <table class="inner-table">
                                    <tr>
                                        <td>REVISIÓN</td>
                                        <td>1</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <table class="inner-table">
                                    <tr>
                                        <td>FECHA DE ELABORACIÓN</td>
                                        <td>-</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <table class="inner-table">
                                    <tr>
                                        <td>ÚLTIMA REVISIÓN</td>
                                        <td>-</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td class="code-cell">FOR-IoT-01</td>
                            <td>
                                <table class="inner-table">
                                    <tr>
                                        <td>ÁREA</td>
                                        <td class="uppercase">{{ $area_name }}</td>
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
                    <td>{{ \Carbon\Carbon::parse($planned_date)->format('d/m/Y') }}</td>
                    <th>Turno</th>
                    <td>{{ $shift_name }}</td>
                    <th>Hora de Inicio y Termino</th>
                    <td>{{ \Carbon\Carbon::parse($shift_start_time)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($shift_end_time)->format('H:i') }}</td>
                </tr>
            </table>

            <!-- Tabla de Producción -->
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
                    @foreach ($records as $record)
                        @php
                            $startTime = $record->production_start
                                ? \Carbon\Carbon::parse($record->production_start)
                                : null;
                            $endTime = $record->production_end ? \Carbon\Carbon::parse($record->production_end) : null;
                            $totalMinutes = $startTime && $endTime ? $startTime->diffInMinutes($endTime) : 0;
                        @endphp
                        <tr>
                            <td>{{ $record->part_number }}</td>
                            <td>{{ $record->shop_order_number }}</td>
                            <td>{{ $record->planned_quantity }}</td>
                            <td>{{ number_format($record->calculated_cycletime, 2) }}</td>
                            <td>{{ $record->calculated_planned_time }}</td>
                            <td>{{ $startTime ? $startTime->format('H:i') : '-' }}</td>
                            <td>{{ $endTime ? $endTime->format('H:i') : '-' }}</td>
                            <td>{{ number_format($totalMinutes, 2) }}</td>
                            <td>{{ number_format($record->calculated_efficiency, 2) }}%</td>
                            <td>{{ $record->produced_quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
