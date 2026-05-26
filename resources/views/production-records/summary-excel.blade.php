<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<?php echo '<?mso-application progid="Excel.Sheet"?>'; ?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:o="urn:schemas-microsoft-com:office:office">

    <Styles>
        {{-- Encabezado --}}
        <Style ss:ID="header">
            <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9" ss:Name="Inter"/>
            <Interior ss:Color="#1E3A5F" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#CBD5E1"/>
            </Borders>
        </Style>

        {{-- Celda normal --}}
        <Style ss:ID="cell">
            <Font ss:Size="9" ss:Name="Inter" ss:Color="#1E293B"/>
            <Alignment ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Celda centrada --}}
        <Style ss:ID="cellCenter">
            <Font ss:Size="9" ss:Name="Inter" ss:Color="#1E293B"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Texto secundario (subtítulo en celda) --}}
        <Style ss:ID="cellMuted">
            <Font ss:Size="8" ss:Name="Inter" ss:Color="#94A3B8"/>
            <Alignment ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Monospace centrado (orden, hora) --}}
        <Style ss:ID="cellMono">
            <Font ss:Size="9" ss:Name="Consolas" ss:Color="#475569"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Badge turno (azul) --}}
        <Style ss:ID="badgePrimary">
            <Font ss:Bold="1" ss:Size="8.5" ss:Name="Inter" ss:Color="#1D4ED8"/>
            <Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Badge cantidad planeada (gris) --}}
        <Style ss:ID="badgeSecondary">
            <Font ss:Bold="1" ss:Size="8.5" ss:Name="Inter" ss:Color="#475569"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
            </Borders>
        </Style>

        {{-- Badge producida: éxito (verde) --}}
        <Style ss:ID="badgeSuccess">
            <Font ss:Bold="1" ss:Size="8.5" ss:Name="Inter" ss:Color="#15803D"/>
            <Interior ss:Color="#F0FDF4" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Badge producida: advertencia (amarillo) --}}
        <Style ss:ID="badgeWarning">
            <Font ss:Bold="1" ss:Size="8.5" ss:Name="Inter" ss:Color="#92400E"/>
            <Interior ss:Color="#FEFCE8" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Badge producida: peligro (rojo) --}}
        <Style ss:ID="badgeDanger">
            <Font ss:Bold="1" ss:Size="8.5" ss:Name="Inter" ss:Color="#B91C1C"/>
            <Interior ss:Color="#FEF2F2" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F1F5F9"/>
            </Borders>
        </Style>

        {{-- Footer totales --}}
        <Style ss:ID="footer">
            <Font ss:Bold="1" ss:Size="9" ss:Name="Inter" ss:Color="#1E293B"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#E2E8F0"/>
            </Borders>
        </Style>

        {{-- Footer label --}}
        <Style ss:ID="footerLabel">
            <Font ss:Bold="1" ss:Size="9" ss:Name="Inter" ss:Color="#64748B"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#E2E8F0"/>
            </Borders>
        </Style>
    </Styles>

    <Worksheet ss:Name="Resumen de Producción">
        <Table ss:DefaultRowHeight="18">
            {{-- Anchos de columna --}}
            <Column ss:Width="60"/>  {{-- Estación N° --}}
            <Column ss:Width="120"/> {{-- Estación Nombre --}}
            <Column ss:Width="80"/>  {{-- N° Parte --}}
            <Column ss:Width="120"/> {{-- Nombre Parte --}}
            <Column ss:Width="70"/>  {{-- Fecha --}}
            <Column ss:Width="55"/>  {{-- Turno --}}
            <Column ss:Width="65"/>  {{-- Planeada --}}
            <Column ss:Width="65"/>  {{-- Producida --}}
            <Column ss:Width="55"/>  {{-- Inicio --}}
            <Column ss:Width="55"/>  {{-- Término --}}
            <Column ss:Width="65"/>  {{-- Tiempo --}}

            {{-- Encabezados --}}
            <Row ss:Height="24">
                <Cell ss:StyleID="header"><Data ss:Type="String">Estación (N°)</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Estación</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">N° de Parte</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Nombre de Parte</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Fecha</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Turno</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Planeada</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Producida</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Inicio</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Término</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">Tiempo (min)</Data></Cell>
            </Row>

            {{-- Filas de datos --}}
            @foreach ($records as $rec)
                @php
                    $prod = $rec->produced_quantity;
                    $plan = $rec->planned_quantity;
                    $pct  = $plan > 0 ? ($prod / $plan) * 100 : 0;

                    if ($pct >= 100)      $prodStyle = 'badgeSuccess';
                    elseif ($pct >= 80)   $prodStyle = 'badgeWarning';
                    else                  $prodStyle = 'badgeDanger';

                    $minutes = ($rec->production_start && $rec->production_end)
                        ? (int) \Carbon\Carbon::parse($rec->production_start)
                              ->diffInMinutes(\Carbon\Carbon::parse($rec->production_end))
                        : null;
                @endphp
                <Row ss:Height="20">
                    <Cell ss:StyleID="cell"><Data ss:Type="String">{{ $rec->work_number }}</Data></Cell>
                    <Cell ss:StyleID="cellMuted"><Data ss:Type="String">{{ $rec->work_name }}</Data></Cell>
                    <Cell ss:StyleID="cell"><Data ss:Type="String">{{ $rec->part_number }}</Data></Cell>
                    <Cell ss:StyleID="cellMuted"><Data ss:Type="String">{{ $rec->part_name }}</Data></Cell>
                    <Cell ss:StyleID="cellMono"><Data ss:Type="String">{{ \Carbon\Carbon::parse($rec->planned_date)->format('d/m/Y') }}</Data></Cell>
                    <Cell ss:StyleID="badgePrimary"><Data ss:Type="String">{{ $rec->shift_name }}</Data></Cell>
                    <Cell ss:StyleID="badgeSecondary"><Data ss:Type="Number">{{ $plan }}</Data></Cell>
                    <Cell ss:StyleID="{{ $prodStyle }}"><Data ss:Type="Number">{{ $prod }}</Data></Cell>
                    <Cell ss:StyleID="cellMono"><Data ss:Type="String">{{ $rec->production_start ? \Carbon\Carbon::parse($rec->production_start)->format('H:i') : '' }}</Data></Cell>
                    <Cell ss:StyleID="cellMono"><Data ss:Type="String">{{ $rec->production_end ? \Carbon\Carbon::parse($rec->production_end)->format('H:i') : '' }}</Data></Cell>
                    <Cell ss:StyleID="cellMono"><Data ss:Type="{{ $minutes !== null ? 'Number' : 'String' }}">{{ $minutes ?? '' }}</Data></Cell>
                </Row>
            @endforeach

            {{-- Fila de totales --}}
            @if ($records->count() > 0)
                <Row ss:Height="22">
                    <Cell ss:StyleID="footerLabel" ss:MergeAcross="5"><Data ss:Type="String">TOTALES</Data></Cell>
                    <Cell ss:StyleID="footer"><Data ss:Type="Number">{{ $records->sum('planned_quantity') }}</Data></Cell>
                    <Cell ss:StyleID="footer"><Data ss:Type="Number">{{ $records->sum('produced_quantity') }}</Data></Cell>
                    <Cell ss:StyleID="footer"><Data ss:Type="String"></Data></Cell>
                    <Cell ss:StyleID="footer"><Data ss:Type="String"></Data></Cell>
                    <Cell ss:StyleID="footer"><Data ss:Type="String"></Data></Cell>
                </Row>
            @endif
        </Table>
    </Worksheet>
</Workbook>
