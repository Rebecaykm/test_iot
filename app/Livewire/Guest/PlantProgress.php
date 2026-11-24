<?php

namespace App\Livewire\Guest;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use Carbon\Carbon;

/**
 * /guest/plant-progress: el avance semanal de toda la planta en un solo árbol
 * Área → Línea → Estación. Cada renglón lleva el % de la semana y, por turno, la misma barra
 * plan/real de /. Dar click en una estación abre sus números de parte (guest.progress.station) en otra ventana.
 * Hereda de LineProgress los filtros (líneas, estaciones, buscador, semana), el caché y los cálculos.
 */
class PlantProgress extends LineProgress
{
    private const BAR_TONE = [
        'good' => 'bg-emerald-500',
        'warn' => 'bg-amber-400',
        'bad' => 'bg-red-500',
        'none' => 'bg-zinc-400',
    ];

    /** Llaves abiertas: "a:{área}", "l:{línea}" */
    public array $open = [];

    public function mount(?int $areaId = null, ?int $lineId = null, ?int $wcId = null): void
    {
        $this->normalizeFilters();
    }

    public function toggle(string $key): void
    {
        if (!preg_match('/^[al]:\d+$/', $key)) {
            return;
        }

        $this->open = in_array($key, $this->open, true)
            ? array_values(array_diff($this->open, [$key]))
            : [...$this->open, $key];
    }

    public function openAll(): void
    {
        $lines = self::catalog()['lines'];
        $this->open = array_values(array_unique(array_merge(
            array_map(fn ($l) => "a:{$l['area_id']}", $lines),
            array_map(fn ($l) => "l:{$l['id']}", $lines),
        )));
    }

    public function closeAll(): void
    {
        $this->open = [];
    }

    /**
     * % de la semana: lo producido contra lo que tocaba hasta ahora (turnos cerrados y en curso).
     * Contra el plan de toda la semana, un martes todo saldría rojo.
     */
    private function stats(array $row, array $columns): array
    {
        $plan = $real = $planDue = $realDue = 0;
        foreach ($columns as $col) {
            $c = $row['cells'][$col['key']];
            if (!$c) {
                continue;
            }
            $plan += $c['plan'];
            $real += $c['real'];
            if ($col['state'] !== 'future') {
                $planDue += $c['plan'];
                $realDue += $c['real'];
            }
        }

        $pct = $planDue > 0 ? (int) round($realDue / $planDue * 100) : null;
        $tone = self::tone($pct);
        [$realTxt, $planTxt] = self::barQty($real, $plan); // compactos ("65k / 696k"); los exactos van en el title

        return [
            'pct' => $pct,
            'text' => "{$realTxt} / {$planTxt}",
            'title' => 'Real ' . number_format($real) . ' de ' . number_format($plan) . ' planeadas en la semana',
            'fill' => $plan > 0 ? min(100, round($real / $plan * 100, 1)) : 0,
            'tone' => self::TONE_CLASS[$tone],
            'bar' => self::BAR_TONE[$tone],
        ];
    }

    public function render()
    {
        $now = Carbon::now();
        $start = $this->weekStart();
        $end = $start->copy()->addDays(6);
        $days = $this->weekDays($start);
        $shifts = $this->shifts();
        $columns = $this->columns($days, $shifts, $now);

        $catalog = self::catalog();
        $catalogWcs = collect($catalog['workCenters']);
        $wcNames = $catalogWcs->pluck('name', 'id')->all();
        $activity = $this->activity();
        $byWc = collect($this->weekRows($start, $end))->groupBy('wc_id');

        // Piezas sin número de orden por estación y estado (No verificado / No planeado / En progreso): una consulta para toda la planta
        $nameToId = array_flip($wcNames);
        $noOrderByWc = [];
        $noOrderRows = $this->cached("plant_progress:v1:no_order:{$start->toDateString()}", $end, fn () => ProductionRecord::getNoOrderParts($catalogWcs->pluck('id')->all(), $start->toDateString(), $end->toDateString())
            ->map(fn ($r) => ['wc' => $r->work_center_name, 'status' => $r->status, 'qty' => (int) $r->quantity])
            ->all());
        foreach ($noOrderRows as $r) {
            if (isset($nameToId[$r['wc']])) {
                $noOrderByWc[$nameToId[$r['wc']]][$r['status']] = ($noOrderByWc[$nameToId[$r['wc']]][$r['status']] ?? 0) + $r['qty'];
            }
        }
        $addStatuses = function (array $into, array $from): array {
            foreach ($from as $status => $qty) {
                $into[$status] = ($into[$status] ?? 0) + $qty;
            }

            return $into;
        };

        // Buscador: solo las estaciones que tienen ese número de parte; se abren solas las áreas y líneas
        $search = mb_strtoupper(trim($this->partSearch));
        $searchWcs = $search === '' ? null : PartNumber::query()->where('number', 'like', "%{$search}%")->pluck('work_center_id')->map(fn ($id) => (int) $id)->all();

        $pickedLines = $this->selectedLines;
        $pickedWcs = $this->selectedWorkCenters;
        $isOpen = fn (string $key) => $searchWcs !== null || in_array($key, $this->open, true);

        // Renglón con su % y las celdas de turno (las mismas de /)
        $make = function (int $level, string $key, string $name, ?string $color, array $records, array $extra = []) use ($columns) {
            $row = $this->buildRow($records, $columns);

            return [
                'key' => $key, 'level' => $level, 'name' => $name, 'color' => $color,
                'noOrder' => $row['noOrder'],
                'stats' => $this->stats($row, $columns),
                'cells' => self::cellsHtml($row['cells'], $columns, [], max($level, 1)),
            ] + $extra;
        };

        $rows = [];
        foreach (collect($catalog['lines'])->groupBy('area_id') as $areaId => $lines) {
            $areaRows = [];
            $areaRecords = [];
            $areaNo = [];
            $areaLive = $areaTotal = 0;

            foreach ($lines as $line) {
                if ($pickedLines && !in_array($line['id'], $pickedLines, true)) {
                    continue;
                }

                $stationRows = [];
                $lineRecords = [];
                $lineNo = [];
                $lineLive = 0;

                foreach ($catalogWcs->where('line_id', $line['id']) as $wc) {
                    if (($pickedWcs && !in_array($wc['id'], $pickedWcs, true))
                        || ($searchWcs !== null && !in_array($wc['id'], $searchWcs, true))
                        || !isset($byWc[$wc['id']])) {
                        continue;
                    }

                    $records = $byWc[$wc['id']]->all();
                    $act = $this->activityFor([$wc['id']], $wcNames, $activity);
                    $live = ($act['state'] ?? null) === 'live';

                    $lineRecords = array_merge($lineRecords, $records);
                    $lineLive += (int) $live;
                    $lineNo = $addStatuses($lineNo, $noOrderByWc[$wc['id']] ?? []);
                    $stationRows[] = $make(2, "w:{$wc['id']}", $wc['name'], null, $records, [
                        'no' => $noOrderByWc[$wc['id']] ?? [],
                        'state' => $act ? ($live ? 'live' : 'stop') : null,
                        // Abre los números de parte de la estación en otra ventana, en la misma semana (y con la misma búsqueda)
                        'href' => route('guest.progress.station', ['workCenter' => $wc['id']] + array_filter(['week' => $this->week, 'date' => $this->date, 'q' => trim($this->partSearch)])),
                    ]);
                }

                if (!$stationRows) {
                    continue;
                }

                $areaRecords = array_merge($areaRecords, $lineRecords);
                $areaLive += $lineLive;
                $areaNo = $addStatuses($areaNo, $lineNo);
                $areaTotal += count($stationRows);
                $areaRows[] = $make(1, "l:{$line['id']}", $line['name'], $line['color'] ?: '#71717a', $lineRecords, [
                    'no' => $lineNo, 'open' => $isOpen("l:{$line['id']}"), 'live' => $lineLive, 'total' => count($stationRows),
                ]);
                if ($isOpen("l:{$line['id']}")) {
                    array_push($areaRows, ...$stationRows);
                }
            }

            if (!$areaRows) {
                continue;
            }

            $rows[] = $make(0, "a:{$areaId}", $lines->first()['area_name'], null, $areaRecords, [
                'no' => $areaNo, 'open' => $isOpen("a:{$areaId}"), 'live' => $areaLive, 'total' => $areaTotal,
            ]);
            if ($isOpen("a:{$areaId}")) {
                array_push($rows, ...$areaRows);
            }
        }

        return view('livewire.guest.plant-progress', [
            'rows' => $rows,
            'days' => $days,
            'shifts' => $shifts,
            'columns' => $columns,
            'weekLabel' => $this->weekLabel($start, $end),
            'isCurrentWeek' => $start->isSameDay(Carbon::today()->startOfWeek(Carbon::MONDAY)),
            'lastUpdated' => $now->format('H:i:s'),
            'searching' => $searchWcs !== null,
        ]);
    }
}
