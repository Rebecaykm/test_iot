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
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            padding: 12px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 10px;
            border: 1px solid #666;
            border-radius: 8px;
        }

        th, td {
            border-right: 1px solid #666;
            border-bottom: 1px solid #666;
            padding: 6px 8px;
        }

        /* Eliminar bordes duplicados en última columna */
        th:last-child,
        td:last-child {
            border-right: none;
        }

        /* Eliminar borde inferior en última fila */
        tr:last-child td,
        tr:last-child th {
            border-bottom: none;
        }

        /* Células con colspan o rowspan no deben mostrar bordes internos */
        .title-cell {
            border-right: 1px solid #666 !important;
        }

        /* Redondear esquinas de las celdas en las esquinas de la tabla */
        table tr:first-child th:first-child,
        table tr:first-child td:first-child {
            border-top-left-radius: 7px;
        }

        table tr:first-child th:last-child,
        table tr:first-child td:last-child {
            border-top-right-radius: 7px;
        }

        table tr:last-child td:first-child,
        table tr:last-child th:first-child {
            border-bottom-left-radius: 7px;
        }

        table tr:last-child td:last-child,
        table tr:last-child th:last-child {
            border-bottom-right-radius: 7px;
        }

        /* Header principal */
        .main-header td {
            vertical-align: middle;
            text-align: center;
        }

        .logo-cell {
            width: 15%;
        }

        .logo-cell img {
            width: 85px;
            display: block;
            margin: 0 auto;
        }

        .code-cell {
            font-weight: bold;
            font-size: 10px;
        }

        .title-cell {
            font-weight: bold;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .metadata-label {
            font-size: 8px;
            font-weight: bold;
            text-align: right;
            width: 12%;
            padding-right: 8px;
        }

        .metadata-value {
            font-size: 8px;
            text-align: left;
            width: 13%;
        }

        /* Información de línea y turno */
        .info-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 9px;
            text-align: left;
            width: 15%;
        }

        .info-table td {
            font-size: 9px;
            text-align: left;
        }

        /* Tabla de producción */
        .data-table thead th {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            padding: 8px 6px;
        }

        .data-table tbody td {
            font-size: 9px;
            text-align: center;
            padding: 7px 4px;
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
    @foreach($groupedByWorkStation as $workNumber => $records)
        <div class="page-break no-break">
            <!-- Header Principal -->
            <table class="main-header">
                <tr>
                    <!-- Logo (3 filas) -->
                    <td class="logo-cell" rowspan="3">
                        <img src="{{ public_path('images/ykm.png') }}" alt="Logo">
                    </td>

                    <!-- Título (3 columnas, 4 filas) -->
                    <td class="title-cell" colspan="3" rowspan="4">
                        REPORTE DE PRODUCCIÓN Y PAROS DE LÍNEA
                    </td>

                    <!-- Metadatos Fila 1 -->
                    <td class="metadata-label">Revisión:</td>
                    <td class="metadata-value">4</td>
                </tr>
                <tr>
                    <!-- Metadatos Fila 2 -->
                    <td class="metadata-label">Fecha Elaboración:</td>
                    <td class="metadata-value">{{ $currentShiftDate->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <!-- Metadatos Fila 3 -->
                    <td class="metadata-label">Última Revisión:</td>
                    <td class="metadata-value">-</td>
                </tr>
                <tr>
                    <!-- FOR-MA-75 (1 fila) -->
                    <td class="code-cell">FOR-MA-75</td>

                    <!-- Metadatos Fila 4 -->
                    <td class="metadata-label">Página / Área:</td>
                    <td class="metadata-value">1 de {{ $groupedByWorkStation->count() }} / MANUFACTURA</td>
                </tr>
            </table>

            <!-- Información de Línea y Estación -->
            <table class="info-table">
                <tr>
                    <th>Línea</th>
                    <td>{{ $records->first()->line_name }}</td>
                    <th>Estación</th>
                    <td>{{ $records->first()->work_name }}</td>
                </tr>
            </table>

            <!-- Información de Turno -->
            <table class="info-table">
                <tr>
                    <th>Turno</th>
                    <td>{{ $records->first()->shift_name }}</td>
                    <th>Inicio Turno</th>
                    <td>{{ \Carbon\Carbon::parse($records->first()->shift_start_time)->format('H:i') }}</td>
                    <th>Fin Turno</th>
                    <td>{{ \Carbon\Carbon::parse($records->first()->shift_end_time)->format('H:i') }}</td>
                </tr>
            </table>

            <!-- Tabla de Producción -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Parte</th>
                        <th>No. Orden</th>
                        <th>Cant. Planeada</th>
                        <th>Tiempo Ciclo</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Tiempo Total</th>
                        <th>Eficiencia (%)</th>
                        <th>Cant. Producida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        @php
                            $startTime = $record->production_start ? \Carbon\Carbon::parse($record->production_start) : null;
                            $endTime = $record->production_end ? \Carbon\Carbon::parse($record->production_end) : null;
                            $totalMinutes = ($startTime && $endTime) ? $startTime->diffInMinutes($endTime) : 0;
                        @endphp
                        <tr>
                            <td>{{ $record->part_number }}</td>
                            <td>{{ $record->shop_order_number }}</td>
                            <td>{{ $record->planned_quantity }}</td>
                            <td>{{ number_format($record->production_rate, 2) }}</td>
                            <td>{{ $startTime ? $startTime->format('H:i') : '-' }}</td>
                            <td>{{ $endTime ? $endTime->format('H:i') : '-' }}</td>
                            <td>{{ $totalMinutes }} Min(s)</td>
                            <td>{{ number_format($record->efficiency, 2) }}%</td>
                            <td>{{ $record->produced_quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
