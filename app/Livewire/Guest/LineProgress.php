<?php

namespace App\Livewire\Guest;

use App\Models\Line;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Avance de producción semanal, en tres vistas con el mismo formato:
 *  1. /                                  un renglón por línea, agrupadas por área
 *  2. /guest/progress/line/{line}        las estaciones de la línea
 *  3. /guest/progress/station/{wc}       los números de parte de la estación
 * Columnas: los 7 días divididos en sus turnos y lo producido sin orden.
 */
class LineProgress extends Component
{
    /**
     * Caché en archivo: para este volumen (toda la planta, ~1,100 grupos por semana)
     * leer del store "database" tardaba casi lo mismo que la consulta; en archivo ~20 ms.
     */
    private const CACHE_STORE = 'file';

    /**
     * Semana actual y futuras: 10 s. Caché simple a propósito: con Cache::flexible
     * (entregar el dato viejo y renovarlo después de responder) cada refresco de 30 s
     * recibía el valor del refresco anterior y la vista iba siempre una vuelta atrás.
     */
    private const LIVE_TTL = 10;

    /** Semanas pasadas: sus datos casi no cambian */
    private const PAST_TTL = 3600;

    /** Catálogos (áreas, estaciones, turnos) */
    private const CATALOG_TTL = 600;

    /**
     * Cumplimiento (%): verde desde GOOD, ámbar desde WARN, rojo abajo. Arriba de
     * OVER es sobreproducción.
     */
    public const GOOD = 95;
    public const WARN = 80;
    public const OVER = 105;

    /** Minutos sin piezas tras los que un registro "En progreso" ya no cuenta como produciendo */
    private const LIVE_MINUTES = 60;

    /** Línea abierta (vista 2) */
    #[Locked]
    public ?int $lineId = null;

    /** Estación abierta (vista 3) */
    #[Locked]
    public ?int $wcId = null;

    /**
     * Lunes de la semana mostrada (Y-m-d). Vacío = semana actual: así una pantalla
     * que se queda abierta pasa sola a la semana nueva y la URL no la congela.
     */
    #[Url(as: 'week', except: '')]
    public string $week = '';

    /** Líneas elegidas en la vista 1 (vacío = todas). Va en la URL y se guarda en el navegador. */
    #[Url(as: 'lines', except: [])]
    public array $selectedLines = [];

    /** Estaciones elegidas en la vista 2 (vacío = todas las de la línea). Va en la URL y se guarda en el navegador. */
    #[Url(as: 'wcs', except: [])]
    public array $selectedWorkCenters = [];

    /** Modales: renglón abierto ("line:3", "wc:12", "part:340"); null = cerrado */
    public ?string $noOrderRow = null;
    public ?string $activityRow = null;

    /**
     * Catálogos para los filtros. Se entregan una sola vez en la página (no viajan
     * con el componente en cada refresco).
     */
    public static function catalog(): array
    {
        return Cache::store(self::CACHE_STORE)->remember('line_progress:v3:catalog', self::CATALOG_TTL, function () {
            $lines = Line::query()
                ->select('lines.id', 'lines.name', 'lines.color', 'lines.area_id', 'areas.name as area_name')
                ->leftJoin('areas', 'lines.area_id', '=', 'areas.id')
                ->whereHas('workCenters')
                ->get()
                ->keyBy('id');

            $workCenters = WorkCenter::query()
                ->select('id', 'name', 'line_id')
                ->whereIn('line_id', $lines->keys())
                ->get()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);

            return [
                'lines' => $lines->map(fn ($line) => [
                    'id' => (int) $line->id,
                    'name' => $line->name,
                    'color' => $line->color,
                    'area_name' => $line->area_name ?: 'Sin área',
                ])->sortBy(fn ($l) => $l['area_name'] . ' ' . $l['name'], SORT_NATURAL | SORT_FLAG_CASE)->all(),
                'workCenters' => $workCenters->map(fn ($wc) => [
                    'id' => (int) $wc->id,
                    'name' => $wc->name,
                    'line_id' => (int) $wc->line_id,
                    'line_name' => $lines[$wc->line_id]->name ?? '',
                ])->values()->all(),
            ];
        });
    }

    public function mount(?int $lineId = null, ?int $wcId = null): void
    {
        $catalog = self::catalog();

        if ($wcId !== null) {
            $wc = collect($catalog['workCenters'])->firstWhere('id', $wcId);
            abort_unless($wc, 404);
            $this->wcId = $wc['id'];
            $this->lineId = $wc['line_id'];
        } elseif ($lineId !== null) {
            abort_unless(isset($catalog['lines'][$lineId]), 404);
            $this->lineId = $lineId;
        }

        $this->normalizeFilters();
    }

    public function updatedSelectedLines(): void
    {
        $this->normalizeFilters();
    }

    public function updatedSelectedWorkCenters(): void
    {
        $this->normalizeFilters();
    }

    /** Enteros y sin duplicados */
    private function normalizeFilters(): void
    {
        $this->selectedLines = array_values(array_unique(array_map('intval', $this->selectedLines)));
        $this->selectedWorkCenters = array_values(array_unique(array_map('intval', $this->selectedWorkCenters)));
    }

    /** Botón "Limpiar filtros" (el navegador borra también la selección guardada) */
    public function clearFilters(): void
    {
        $this->selectedLines = [];
        $this->selectedWorkCenters = [];
    }

    // Modales

    public function showNoOrder(string $row): void
    {
        $this->activityRow = null;
        $this->noOrderRow = $row;
    }

    public function showActivity(string $row): void
    {
        $this->noOrderRow = null;
        $this->activityRow = $row;
    }

    public function closeModals(): void
    {
        $this->noOrderRow = null;
        $this->activityRow = null;
    }

    // Semana

    private function weekStart(): Carbon
    {
        $current = Carbon::today()->startOfWeek(Carbon::MONDAY);

        if ($this->week === '') {
            return $current;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $this->week)->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            return $current;
        }
    }

    /** La semana actual se guarda vacía (ver $week) */
    private function setWeek(Carbon $monday): void
    {
        $this->week = $monday->isSameDay(Carbon::today()->startOfWeek(Carbon::MONDAY)) ? '' : $monday->toDateString();
        $this->closeModals();
    }

    public function prevWeek(): void
    {
        $this->setWeek($this->weekStart()->subWeek());
    }

    public function nextWeek(): void
    {
        $this->setWeek($this->weekStart()->addWeek());
    }

    public function currentWeek(): void
    {
        $this->setWeek(Carbon::today()->startOfWeek(Carbon::MONDAY));
    }

    // Datos

    /**
     * Cachea con el TTL según la semana: las pasadas por 1 hora; la actual y las
     * futuras por 10 s (compartido entre todas las pantallas, a lo más 10 s de retraso).
     */
    private function cached(string $key, Carbon $weekEnd, callable $callback): mixed
    {
        $ttl = $weekEnd->lt(Carbon::today()) ? self::PAST_TTL : self::LIVE_TTL;

        return Cache::store(self::CACHE_STORE)->remember($key, $ttl, $callback);
    }

    /**
     * Totales de TODA la planta para la semana por estación, turno y fecha, como
     * arreglos simples (no modelos: serializados pesan ~10 veces menos). Una sola
     * entrada de caché compartida por todas las pantallas; cada una filtra después.
     */
    private function weekRows(Carbon $start, Carbon $end): array
    {
        return $this->cached("line_progress:v4:week:{$start->toDateString()}", $end, function () use ($start, $end) {
            return ProductionRecord::getLineWeeklyProgress([], $start->toDateString(), $end->toDateString())
                ->map(fn ($r) => [
                    'line_id' => (int) $r->line_id,
                    'wc_id' => (int) $r->work_center_id,
                    'shift' => $r->shift,
                    'date' => Carbon::parse($r->planned_date)->toDateString(),
                    'plan' => (int) $r->plan,
                    'real' => (int) $r->with_order,   // real = producido con orden
                    'no_order' => (int) $r->no_order, // sin orden: columna "Sin orden"
                    'in_progress' => (int) $r->in_progress > 0,
                ])
                ->all();
        });
    }

    /** Lo mismo que weekRows(), pero por número de parte y solo de una estación (vista 3) */
    private function partRows(Carbon $start, Carbon $end, int $wcId): array
    {
        return $this->cached("line_progress:v4:parts:{$start->toDateString()}:{$wcId}", $end, function () use ($start, $end, $wcId) {
            return ProductionRecord::getPartWeeklyProgress($start->toDateString(), $end->toDateString(), null, $wcId)
                ->map(fn ($r) => [
                    'part_id' => (int) $r->part_number_id,
                    'part_number' => $r->part_number,
                    'shift' => $r->shift,
                    'date' => Carbon::parse($r->planned_date)->toDateString(),
                    'plan' => (int) $r->plan,
                    'real' => (int) $r->with_order,
                    'no_order' => (int) $r->no_order,
                    'in_progress' => (int) $r->in_progress > 0,
                ])
                ->all();
        });
    }

    /**
     * Qué se produce ahora en cada estación y, si nada, qué fue lo último.
     *  - wc[id]['live']:  números de parte produciendo (con hora de inicio)
     *  - wc[id]['last']:  la última corrida: partes cuya última pieza cayó dentro de
     *                     5 min de la más reciente (p. ej. izquierda/derecha del mismo troquel)
     *  - part["wc|part"]: estado de cada número de parte (vista 3)
     * Toda la planta, compartido por todas las pantallas.
     */
    private function activity(): array
    {
        return Cache::store(self::CACHE_STORE)->remember('line_progress:v1:activity', self::LIVE_TTL, function () {
            $byWc = [];
            $parts = [];
            foreach (ProductionRecord::getWorkCenterActivity(Carbon::now(), self::LIVE_MINUTES) as $r) {
                $item = [
                    'number' => $r->part_number,
                    'name' => $r->part_name,
                    'since' => $r->live_since ? Carbon::parse($r->live_since)->format('Y-m-d H:i:s') : null,
                    'at' => Carbon::parse($r->last_end)->format('Y-m-d H:i:s'),
                    'live' => (int) $r->live === 1,
                ];
                $byWc[(int) $r->work_center_id][] = $item;
                $parts[(int) $r->work_center_id . '|' . (int) $r->part_number_id] = $item;
            }

            $wcs = [];
            foreach ($byWc as $wcId => $items) {
                $live = array_values(array_filter($items, fn ($i) => $i['live']));
                usort($live, fn ($a, $b) => strnatcasecmp($a['number'], $b['number']));

                $lastAt = max(array_column($items, 'at'));
                $runFrom = Carbon::parse($lastAt)->subMinutes(5)->format('Y-m-d H:i:s');
                $last = array_values(array_filter($items, fn ($i) => $i['at'] >= $runFrom));
                usort($last, fn ($a, $b) => strcmp($b['at'], $a['at']));

                $wcs[$wcId] = ['live' => $live, 'last' => $last];
            }

            return ['wc' => $wcs, 'part' => $parts];
        });
    }

    /**
     * Turnos con su horario. Llave propia: /guest/part-progress cachea
     * 'line_progress:shifts' sin las horas.
     */
    private function shifts(): array
    {
        return Cache::store(self::CACHE_STORE)->remember('line_progress:shifts:v2', self::CATALOG_TTL, fn () => Shift::getAllShifts()
            ->map(fn ($s) => [
                'abbr' => $s->abbreviation,
                'name' => $s->name,
                'start' => Carbon::parse($s->start_time)->format('H:i:s'),
                'end' => Carbon::parse($s->end_time)->format('H:i:s'),
            ])
            ->values()
            ->all());
    }

    /**
     * Columnas de datos: cada día dividido en sus turnos, con el estado del turno
     * (cerrado, en curso o por venir) y, si está en curso, qué fracción ya pasó.
     */
    private function columns(array $days, array $shifts, Carbon $now): array
    {
        $columns = [];
        foreach ($days as $day) {
            foreach ($shifts as $si => $shift) {
                $from = Carbon::parse("{$day['date']} {$shift['start']}");
                $to = Carbon::parse("{$day['date']} {$shift['end']}");
                if ($to->lte($from)) {
                    $to->addDay(); // turno que cruza la medianoche
                }

                $state = $now->gte($to) ? 'closed' : ($now->gte($from) ? 'live' : 'future');

                $columns[] = [
                    'key' => $shift['abbr'] . '|' . $day['date'],
                    'abbr' => $shift['abbr'],
                    'name' => $shift['name'],
                    'first' => $si === 0,
                    'isToday' => $day['isToday'],
                    'title' => $shift['name'] . ' ' . $day['name'] . ' ' . $day['label'],
                    'state' => $state,
                    'elapsed' => match ($state) {
                        'closed' => 1.0,
                        'live' => $from->diffInSeconds($now) / max(1, $from->diffInSeconds($to)),
                        default => 0.0,
                    },
                    // Un registro "En progreso" solo se marca en vivo si su turno no terminó hace más de un día
                    'canBeLive' => $to->gte($now->copy()->subDay()),
                ];
            }
        }

        return $columns;
    }

    /**
     * Color del cumplimiento: lo real contra lo que debía llevar a esta hora ($due:
     * todo el plan si el turno cerró, la parte proporcional si está en curso).
     * Un turno por venir no se califica; producir de más que el plan es sobreproducción.
     */
    public static function tone(int $plan, int $real, float $due): string
    {
        if ($plan <= 0) {
            return 'none';
        }
        if ($real * 100 > $plan * self::OVER) {
            return 'over';
        }
        if ($due <= 0) {
            return 'none';
        }

        $ratio = $real / $due * 100;

        return $ratio >= self::GOOD ? 'good' : ($ratio >= self::WARN ? 'warn' : 'bad');
    }

    /** Celda de un turno */
    private function cell(int $plan, int $real, bool $inProgress, array $col): ?array
    {
        $live = $inProgress && $col['canBeLive'];

        if ($plan === 0 && $real === 0 && !$live) {
            return null;
        }

        return [
            'plan' => $plan,
            'real' => $real,
            'live' => $live,
            'pct' => $plan > 0 ? (int) round($real / $plan * 100) : null,
            'fill' => $plan > 0 ? min(100, round($real / $plan * 100, 1)) : 0,
            'tone' => self::tone($plan, $real, $plan * $col['elapsed']),
            'future' => $col['state'] === 'future' && $real === 0,
        ];
    }

    /** Renglón (área, estación o número de parte): celdas por turno y lo producido sin orden */
    private function buildRow(array $rows, array $columns): array
    {
        $sums = [];
        $noOrder = 0;
        foreach ($rows as $r) {
            $k = $r['shift'] . '|' . $r['date'];
            $sums[$k]['plan'] = ($sums[$k]['plan'] ?? 0) + $r['plan'];
            $sums[$k]['real'] = ($sums[$k]['real'] ?? 0) + $r['real'];
            $sums[$k]['live'] = ($sums[$k]['live'] ?? false) || $r['in_progress'];
            $noOrder += $r['no_order'];
        }

        $cells = [];
        foreach ($columns as $col) {
            $s = $sums[$col['key']] ?? null;
            $cells[$col['key']] = $s ? $this->cell($s['plan'], $s['real'], $s['live'], $col) : null;
        }

        return ['cells' => $cells, 'noOrder' => $noOrder];
    }

    /**
     * HTML de las celdas de turno de un renglón. Se arma aquí y no en Blade (igual
     * que PartProgress::cellsHtml): cada @if de Blade agrega marcadores de Livewire y,
     * con la indentación, el HTML de cada refresco pesaba el doble.
     * La celda lleva el % arriba a la derecha (con el color del cumplimiento) y la
     * barra: plan = contorno gris, real = relleno verde.
     */
    public static function cellsHtml(array $cells, array $columns): string
    {
        $html = '';
        foreach ($columns as $col) {
            $c = $cells[$col['key']];
            $class = ($col['first'] ? 'lp-td-day' : 'pp-td-shift') . ($col['isToday'] ? ' pp-today' : '');

            if (!$c) {
                $html .= "<td class=\"{$class} pp-x\"></td>";
                continue;
            }

            $title = $col['title'] . ' · Plan ' . number_format($c['plan']) . ' · Real ' . number_format($c['real'])
                . ($c['pct'] !== null ? " ({$c['pct']}%)" : '')
                . ($c['live'] ? ' · En proceso' : '');

            // Un turno por venir muestra 0 % sin color (tono 'none')
            $top = $c['pct'] === null
                ? '<span class="lp-sub">Sin plan</span>'
                : "<span class=\"lp-pct lp-t-{$c['tone']}\">{$c['pct']}%</span>";

            $realHtml = '<span class="pp-real' . ($c['plan'] > 0 && $c['real'] >= $c['plan'] ? ' pp-done' : '') . '">' . number_format($c['real']) . '</span>';
            $bar = $c['plan'] > 0
                ? '<div class="pp-bar' . ($c['future'] ? ' lp-bar-future' : '') . '">'
                    . ($c['fill'] > 0 ? "<div class=\"pp-fill\" style=\"width:calc({$c['fill']}% + 2px)\"></div>" : '')
                    . '<div class="pp-bar-txt">' . ($c['future'] ? '<span></span>' : $realHtml) . '<span class="pp-plan">/ ' . number_format($c['plan']) . '</span></div></div>'
                : "<div class=\"pp-nums\">{$realHtml}<span class=\"pp-plan\">pzs</span></div>";

            $html .= "<td class=\"{$class}\"><div class=\"lp-c" . ($c['live'] ? ' lp-c-live' : '') . '" title="' . e($title) . '">'
                . ($c['live'] ? '<span class="lp-dot"></span>' : '')
                . "<div class=\"lp-top\">{$top}</div>{$bar}</div></td>";
        }

        return $html;
    }

    /** "Hoy 14:20", "Ayer 23:05" o "Lun 19 Oct · 14:20" */
    private function timeLabel(string $ts, Carbon $now): string
    {
        $t = Carbon::parse($ts);
        $days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return match (true) {
            $t->isSameDay($now) => 'Hoy ' . $t->format('H:i'),
            $t->isSameDay($now->copy()->subDay()) => 'Ayer ' . $t->format('H:i'),
            default => $days[$t->dayOfWeek] . ' ' . $t->day . ' ' . $meses[$t->month - 1] . ' · ' . $t->format('H:i'),
        };
    }

    /** "desde 14:20" (hoy) o "desde Ayer 23:05" */
    private function sinceLabel(?string $ts, Carbon $now): string
    {
        return $ts ? 'desde ' . preg_replace('/^Hoy /', '', $this->timeLabel($ts, $now)) : 'en proceso';
    }

    /**
     * Qué se está produciendo en unas estaciones o, si nada, lo último: números de
     * parte con su estación y la hora. Con varias estaciones, lo último es la última
     * corrida de cada una, de la más reciente a la más vieja.
     */
    private function activityFor(array $wcIds, array $wcNames, array $activity, Carbon $now): ?array
    {
        $live = [];
        $last = [];
        foreach ($wcIds as $wcId) {
            $a = $activity['wc'][$wcId] ?? null;
            if (!$a) {
                continue;
            }
            foreach ($a['live'] as $i) {
                $live[] = $i + ['station' => $wcNames[$wcId] ?? ''];
            }
            foreach ($a['last'] as $i) {
                $last[] = $i + ['station' => $wcNames[$wcId] ?? ''];
            }
        }

        if ($live) {
            usort($live, fn ($a, $b) => strnatcasecmp($a['station'] . ' ' . $a['number'], $b['station'] . ' ' . $b['number']));

            return ['state' => 'live', 'items' => array_map(fn ($i) => $i + ['time' => $this->sinceLabel($i['since'], $now)], $live)];
        }

        if ($last) {
            usort($last, fn ($a, $b) => strcmp($b['at'], $a['at']));

            return ['state' => 'last', 'items' => array_map(fn ($i) => $i + ['time' => $this->timeLabel($i['at'], $now)], $last)];
        }

        return null;
    }

    /** URL de una vista conservando la semana elegida */
    private function link(string $route, array|int $params = []): string
    {
        return route($route, $params) . ($this->week !== '' ? '?week=' . $this->week : '');
    }

    public function render()
    {
        $now = Carbon::now();
        $start = $this->weekStart();
        $end = $start->copy()->addDays(6); // domingo

        $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        // Encabezados de los 7 días (lunes a domingo)
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = [
                'name' => $dayNames[$i],
                'label' => $d->day . ' ' . $meses[$d->month - 1],
                'date' => $d->toDateString(),
                'isToday' => $d->isToday(),
            ];
        }

        $shifts = $this->shifts();
        $columns = $this->columns($days, $shifts, $now);
        $catalog = self::catalog();
        $catalogWcs = collect($catalog['workCenters']);
        $wcNames = $catalogWcs->pluck('name', 'id')->all();
        $activity = $this->activity();

        $line = $this->lineId !== null ? ($catalog['lines'][$this->lineId] ?? null) : null;
        $wc = $this->wcId !== null ? $catalogWcs->firstWhere('id', $this->wcId) : null;
        $level = $wc ? 3 : ($line ? 2 : 1);

        // Renglones de la vista, en grupos (las líneas van agrupadas por área; las demás vistas, un solo grupo)
        $groups = [];
        if ($level === 3) {
            $rows = [];
            foreach (collect($this->partRows($start, $end, $wc['id']))->groupBy('part_id') as $partId => $records) {
                $state = $activity['part'][$wc['id'] . '|' . $partId] ?? null;
                $rows[] = $this->buildRow($records->all(), $columns) + [
                    'key' => "part:{$partId}",
                    'name' => $records->first()['part_number'],
                    'color' => null,
                    'href' => null,
                    'activity' => $state ? [
                        'state' => $state['live'] ? 'live' : 'last',
                        'items' => [$state + ['station' => '', 'time' => $state['live'] ? $this->sinceLabel($state['since'], $now) : $this->timeLabel($state['at'], $now)]],
                    ] : null,
                ];
            }
            usort($rows, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
            $groups[] = ['name' => null, 'rows' => $rows];
        } elseif ($level === 2) {
            // Estaciones de la línea; si hay estaciones elegidas de esta línea, solo esas
            $lineWcs = $catalogWcs->where('line_id', $line['id']);
            $picked = $lineWcs->whereIn('id', $this->selectedWorkCenters);
            $scope = ($picked->isNotEmpty() ? $picked : $lineWcs)->pluck('id')->all();

            $byWc = collect($this->weekRows($start, $end))->whereIn('wc_id', $scope)->groupBy('wc_id');
            $rows = [];
            foreach ($lineWcs->whereIn('id', $byWc->keys()->all()) as $w) {
                $rows[] = $this->buildRow($byWc[$w['id']]->all(), $columns) + [
                    'key' => "wc:{$w['id']}",
                    'name' => $w['name'],
                    'color' => null,
                    'href' => $this->link('guest.progress.station', $w['id']),
                    'activity' => $this->activityFor([$w['id']], $wcNames, $activity, $now),
                ];
            }
            $groups[] = ['name' => null, 'rows' => $rows];
        } else {
            $byLine = collect($this->weekRows($start, $end))->groupBy('line_id');
            foreach ($catalog['lines'] as $l) {
                if (($this->selectedLines && !in_array($l['id'], $this->selectedLines, true)) || !isset($byLine[$l['id']])) {
                    continue;
                }
                $groups[$l['area_name']]['name'] = $l['area_name'];
                $groups[$l['area_name']]['rows'][] = $this->buildRow($byLine[$l['id']]->all(), $columns) + [
                    'key' => "line:{$l['id']}",
                    'name' => $l['name'],
                    'color' => $l['color'] ?: '#71717a',
                    'href' => $this->link('guest.progress.line', $l['id']),
                    'activity' => $this->activityFor($catalogWcs->where('line_id', $l['id'])->pluck('id')->all(), $wcNames, $activity, $now),
                ];
            }
        }
        $groups = array_values(array_filter($groups, fn ($g) => $g['rows']));
        $rowsByKey = array_column(array_merge([], ...array_column($groups, 'rows')), null, 'key');

        // Modal "Sin orden": números de parte del renglón abierto, por estación y turno
        $noOrderDetail = null;
        if ($this->noOrderRow && isset($rowsByKey[$this->noOrderRow])) {
            [$type, $id] = explode(':', $this->noOrderRow);
            $id = (int) $id;
            $wcIds = match ($type) {
                'line' => $catalogWcs->where('line_id', $id)->pluck('id')->all(),
                'wc' => [$id],
                default => [$wc['id']],
            };
            $partId = $type === 'part' ? $id : null;
            $key = 'line_progress:v3:no_order:' . $start->toDateString() . ':' . md5(implode(',', $wcIds) . '|' . $partId);
            $parts = $this->cached($key, $end, function () use ($wcIds, $start, $end, $partId) {
                $pivot = [];
                foreach (ProductionRecord::getNoOrderParts($wcIds, $start->toDateString(), $end->toDateString(), $partId) as $p) {
                    $k = $p->work_center_name . '|' . $p->part_number;
                    $pivot[$k] ??= ['station' => $p->work_center_name, 'number' => $p->part_number, 'name' => $p->part_name, 'shifts' => [], 'total' => 0];
                    $pivot[$k]['shifts'][$p->shift] = ($pivot[$k]['shifts'][$p->shift] ?? 0) + (int) $p->quantity;
                    $pivot[$k]['total'] += (int) $p->quantity;
                }
                $pivot = array_values($pivot);
                usort($pivot, fn ($a, $b) => $b['total'] <=> $a['total']);

                return $pivot;
            });

            $noOrderDetail = [
                'title' => $rowsByKey[$this->noOrderRow]['name'],
                'withStation' => count($wcIds) > 1,
                'parts' => $parts,
                'total' => array_sum(array_column($parts, 'total')),
            ];
        }

        // Modal de producción: todos los números de parte produciendo (o los últimos) del renglón
        $activityDetail = null;
        if ($this->activityRow && isset($rowsByKey[$this->activityRow]['activity'])) {
            $activityDetail = ['title' => $rowsByKey[$this->activityRow]['name']] + $rowsByKey[$this->activityRow]['activity'];
        }

        return view('livewire.guest.line-progress', [
            'level' => $level,
            // Botón "Volver": de los números de parte a las estaciones de la línea; de las estaciones al inicio
            'backLink' => match ($level) {
                3 => $this->link('guest.progress.line', $line['id']),
                2 => $this->link('guest.line-progress'),
                default => null,
            },
            'line' => $line,
            'wc' => $wc,
            'days' => $days,
            'shifts' => $shifts,
            'columns' => $columns,
            'groups' => $groups,
            'noOrderDetail' => $noOrderDetail,
            'activityDetail' => $activityDetail,
            // "19 – 25 Oct"; si cruza de mes, "28 Sep – 4 Oct"; el año solo si no es el actual
            'weekLabel' => $start->day . ($start->month !== $end->month ? ' ' . $meses[$start->month - 1] : '')
                . ' – ' . $end->day . ' ' . $meses[$end->month - 1] . ($end->year !== Carbon::today()->year ? ' ' . $end->year : ''),
            'isCurrentWeek' => $start->isSameDay(Carbon::today()->startOfWeek(Carbon::MONDAY)),
            'lastUpdated' => $now->format('H:i:s'),
        ]);
    }
}
