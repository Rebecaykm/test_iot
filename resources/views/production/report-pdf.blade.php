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

        /* ===== TABLAS CON BORDES REDONDEADOS ===== */
        .card-table {
            width: 100%;
            border: 0.5px solid #666;
            border-radius: 8px;
            overflow: hidden;
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

        /* ===== TABLA INTERNA (sin bordes) ===== */
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

        /* ===== ESTILOS DEL ENCABEZADO ===== */
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

        /* Información de línea y turno */
        .info-table {
            width: 100%;
            border: 0.5px solid #666;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .info-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 9px;
            text-align: left;
            width: 15%;
            padding: 6px 8px;
            border: 0.5px solid #666;
        }

        .info-table td {
            font-size: 9px;
            text-align: left;
            padding: 6px 8px;
            border: 0.5px solid #666;
        }

        /* Tabla de producción */
        .data-table {
            width: 100%;
            border: 0.5px solid #666;
            border-radius: 8px;
            overflow: hidden;
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
        }

        /* Salto de página */
        .page-break {
            page-break-after: always;
        }

        .no-break:last-child {
            page-break-after: auto;
        }
    </style>
</head>

<body>
    @php
        $stationIndex = 0;
    @endphp
    @foreach ($groupedByWorkStation as $workNumber => $records)
        @php
            $stationIndex++;
            $totalStations = $groupedByWorkStation->count();
        @endphp
        <div class="page-break no-break">
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
                                REPORTE DE PRODUCCIÓN Y PAROS DE LÍNEA
                            </td>

                            <td>
                                <table class="inner-table">
                                    <tr>
                                        <td>REVISIÓN</td>
                                        <td>4</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <table class="inner-table">
                                    <tr>
                                        <td>FECHA DE ELABORACIÓN</td>
                                        <td>{{ $currentShiftDate->format('d/m/Y') }}</td>
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
                            <td class="code-cell">FOR-MA-75</td>
                            <td>
                                <table class="inner-table">
                                    <tr>
                                        <td>PÁGINA / ÁREA</td>
                                        <td>{{ $stationIndex }} de {{ $totalStations }} / MANUFACTURA</td>
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
                    <th>Línea</th>
                    <td>{{ $records->first()->line_name }}</td>
                    <th>Estación</th>
                    <td>{{ $records->first()->work_name }}</td>
                    <th></th>
                    <td></td>
                </tr>
                <tr>
                    <th>Turno</th>
                    <td>{{ $records->first()->shift_name }}</td>
                    <th>Fecha</th>
                    <td>{{ $currentShiftDate->format('d/m/Y') }}</td>
                    <th>Hora de Inicio</th>
                    <td>{{ \Carbon\Carbon::parse($records->first()->shift_start_time)->format('H:i') }}</td>
                    <th>Hora de Termino</th>
                    <td>{{ \Carbon\Carbon::parse($records->first()->shift_end_time)->format('H:i') }}</td>
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
                        <th>Piezas OK</th>
                        <th>Piezas Semidestructivas</th>
                        <th>Piezas Destructivas</th>
                        <th>Retrabajo</th>
                        <th>Código de Retrabajo</th>
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
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
