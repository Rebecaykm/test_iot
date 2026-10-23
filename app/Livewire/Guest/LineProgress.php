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

    /** v4: el catálogo ya no incluye las líneas ocultas (lines.show_in_progress) */
    private const CATALOG_KEY = 'line_progress:v4:catalog';

    /** Color del %: rojo hasta WARN (70 %), ámbar arriba de eso y verde desde GOOD (91 %) */
    public const GOOD = 91;
    public const WARN = 70;

    /**
     * Clase CSS de cada color del %. Van escritas completas a propósito: Tailwind solo
     * genera las clases que encuentra literalmente en el código, y con 'lp-t-' . $tone
     * las descartaba (el % salía sin color).
     */
    private const TONE_CLASS = [
        'good' => 'lp-t-good',
        'warn' => 'lp-t-warn',
        'bad' => 'lp-t-bad',
        'none' => 'lp-t-none',
    ];

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

    /** Día elegido en el selector de fecha (Y-m-d): se resalta en la tabla. Vacío = se resalta hoy. */
    #[Url(as: 'date', except: '')]
    public string $date = '';

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
        return Cache::store(self::CACHE_STORE)->remember(self::CATALOG_KEY, self::CATALOG_TTL, function () {
            $lines = Line::query()
                ->select('lines.id', 'lines.name', 'lines.color', 'lines.area_id', 'areas.name as area_name')
                ->leftJoin('areas', 'lines.area_id', '=', 'areas.id')
                ->whereHas('workCenters')
                ->where('lines.show_in_progress', true)
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

    /**
     * Descarta el catálogo en caché para que los cambios en líneas y estaciones
     * se vean de inmediato y no hasta que venza (CATALOG_TTL).
     */
    public static function forgetCatalog(): void
    {
        Cache::store(self::CACHE_STORE)->forget(self::CATALOG_KEY);
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

    /** La semana actual se guarda vacía (ver $week). Al moverse de semana se vuelve a resaltar hoy. */
    private function setWeek(Carbon $monday): void
    {
        $this->week = $monday->isSameDay(Carbon::today()->startOfWeek(Carbon::MONDAY)) ? '' : $monday->toDateString();
        $this->date = '';
        $this->closeModals();
    }

    /** Selector de fecha: lleva a la semana de ese día y lo resalta */
    public function goToDate(string $date): void
    {
        try {
            $day = Carbon::createFromFormat('!Y-m-d', $date);
        } catch (\Throwable) {
            return; // fecha incompleta mientras se escribe
        }

        $this->setWeek($day->copy()->startOfWeek(Carbon::MONDAY));
        $this->date = $day->isToday() ? '' : $day->toDateString();
    }

    /** Día resaltado: el elegido o, si no hay, hoy */
    private function highlightDate(): string
    {
        try {
            return $this->date !== '' ? Carbon::createFromFormat('!Y-m-d', $this->date)->toDateString() : Carbon::today()->toDateString();
        } catch (\Throwable) {
            return Carbon::today()->toDateString();
        }
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
     * Órdenes de cada número de parte por turno y fecha (vista 3), para el detalle que
     * aparece al pasar el mouse por la barra:
     * [número de parte]["D|2026-10-23"] => ['Orden 123: Plan 100 · Real 80', ...]
     */
    private function partOrders(Carbon $start, Carbon $end, string $wcName): array
    {
        return $this->cached("line_progress:v1:orders:{$start->toDateString()}:" . md5($wcName), $end, function () use ($start, $end, $wcName) {
            $orders = [];
            foreach (ProductionRecord::getWorkCenterWeeklyProgress($wcName, $start->toDateString(), $end->toDateString()) as $r) {
                $orders[$r->part_number][$r->shift . '|' . Carbon::parse($r->planned_date)->toDateString()][] =
                    'Orden ' . $r->order_number . ': Plan ' . number_format((int) $r->planned_quantity) . ' · Real ' . number_format((int) $r->produced_quantity);
            }

            return $orders;
        });
    }

    /**
     * Qué se produce ahora en cada estación y, si nada, qué fue lo último.
     *  - wc[id]['live']:  números de parte produciendo
     *  - wc[id]['last']:  la última corrida: partes cuya última pieza cayó dentro de
     *                     5 min de la más reciente (p. ej. izquierda/derecha del mismo troquel)
     *  - part["wc|part"]: estado de cada número de parte (vista 3)
     * Toda la planta, compartido por todas las pantallas.
     */
    private function activity(): array
    {
        return Cache::store(self::CACHE_STORE)->remember('line_progress:v2:activity', self::LIVE_TTL, function () {
            $byWc = [];
            $parts = [];
            foreach (ProductionRecord::getWorkCenterActivity(Carbon::now(), self::LIVE_MINUTES) as $r) {
                // Inicio: el de los registros en vivo; si ya no está en vivo, el del registro más reciente
                $start = (int) $r->live === 1 ? $r->live_since : $r->last_start;
                $item = [
                    'number' => $r->part_number,
                    'name' => $r->part_name,
                    'at' => Carbon::parse($r->last_end)->format('Y-m-d H:i:s'),
                    'start' => $start ? Carbon::parse($start)->format('Y-m-d H:i:s') : null,
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
     * (cerrado, en curso o por venir).
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
                    'highlight' => $day['highlight'],
                    'title' => $shift['name'] . ' ' . $day['name'] . ' ' . $day['label'],
                    'state' => $state,
                    // Un registro "En progreso" solo se marca en vivo si su turno no terminó hace más de un día
                    'canBeLive' => $to->gte($now->copy()->subDay()),
                ];
            }
        }

        return $columns;
    }

    /** Color del % que se muestra (real / plan): rojo ≤ 70, ámbar 71–90, verde ≥ 91 */
    public static function tone(?int $pct): string
    {
        if ($pct === null) {
            return 'none';
        }

        return $pct >= self::GOOD ? 'good' : ($pct > self::WARN ? 'warn' : 'bad');
    }

    /** Celda de un turno */
    private function cell(int $plan, int $real, bool $inProgress, array $col): ?array
    {
        $live = $inProgress && $col['canBeLive'];

        if ($plan === 0 && $real === 0 && !$live) {
            return null;
        }

        $pct = $plan > 0 ? (int) round($real / $plan * 100) : null;

        return [
            'plan' => $plan,
            'real' => $real,
            'live' => $live,
            'pct' => $pct,
            'fill' => $plan > 0 ? min(100, round($real / $plan * 100, 1)) : 0,
            // Un turno que aún no empieza y sin piezas queda en 0 % sin color
            'tone' => $col['state'] === 'future' && $real === 0 ? 'none' : self::tone($pct),
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
    public static function cellsHtml(array $cells, array $columns, array $orders = []): string
    {
        $html = '';
        foreach ($columns as $col) {
            $c = $cells[$col['key']];
            $class = $col['first'] ? 'lp-td-day' : 'pp-td-shift';

            if (!$c) {
                $html .= "<td class=\"{$class} pp-x\"></td>";
                continue;
            }

            // Detalle al pasar el mouse: turno y día, plan / real y, en la vista de números de parte, sus órdenes
            $title = $col['title'] . "\nPlan " . number_format($c['plan']) . ' · Real ' . number_format($c['real'])
                . ($c['pct'] !== null ? " ({$c['pct']}%)" : '')
                . ($c['live'] ? ' · En proceso' : '')
                . (!empty($orders[$col['key']]) ? "\n" . implode("\n", $orders[$col['key']]) : '');

            // Arriba: % a la izquierda (solo el % lleva el color) y el punto verde de "en proceso" a la derecha
            $top = ($c['pct'] === null ? '<span class="lp-sub">Sin plan</span>' : '<span class="lp-pct ' . self::TONE_CLASS[$c['tone']] . "\">{$c['pct']}%</span>")
                . ($c['live'] ? '<span class="lp-dot lp-dot-green"></span>' : '');

            // Barra igual que /guest/part-progress: plan = contorno gris, real = relleno verde, "real / plan" adentro
            $realHtml = '<span class="pp-real' . ($c['plan'] > 0 && $c['real'] >= $c['plan'] ? ' pp-done' : '') . '">' . number_format($c['real']) . '</span>';
            $bar = $c['plan'] > 0
                ? '<div class="pp-bar">'
                    . ($c['fill'] > 0 ? "<div class=\"pp-fill\" style=\"width:calc({$c['fill']}% + 2px)\"></div>" : '')
                    . "<div class=\"pp-bar-txt\">{$realHtml}<span class=\"pp-plan\">/ " . number_format($c['plan']) . '</span></div></div>'
                : "<div class=\"pp-nums\">{$realHtml}<span class=\"pp-plan\">pzs</span></div>";

            $html .= "<td class=\"{$class}\"><div class=\"pp-cell\" title=\"" . e($title) . '">'
                . "<div class=\"lp-top\">{$top}</div>{$bar}</div></td>";
        }

        return $html;
    }

    /** Fecha corta: "23/10 14:20" (día/mes hora:minutos) */
    private function timeLabel(?string $ts): string
    {
        return $ts ? Carbon::parse($ts)->format('d/m H:i') : '—';
    }

    /**
     * Qué se está produciendo en unas estaciones o, si nada, lo último: números de
     * parte con su estación y la hora. Con varias estaciones, lo último es la última
     * corrida de cada una, de la más reciente a la más vieja.
     */
    private function activityFor(array $wcIds, array $wcNames, array $activity): ?array
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

        $items = $live ?: $last;
        if (!$items) {
            return null;
        }

        usort($items, fn ($a, $b) => strcmp($b['at'], $a['at']));

        // EN PROCESO: desde cuándo corre (el inicio más antiguo); DETENIDO: cuándo salió la última pieza
        $starts = array_filter(array_column($items, 'start'));

        return [
            'state' => $live ? 'live' : 'last',
            'time' => $live ? $this->timeLabel($starts ? min($starts) : null) : $this->timeLabel($items[0]['at']),
            'items' => array_map(fn ($i) => $i + ['startLabel' => $this->timeLabel($i['start']), 'endLabel' => $this->timeLabel($i['at'])], $items),
        ];
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

        // Encabezados de los 7 días (lunes a domingo); se resalta el día elegido o hoy
        $highlight = $this->highlightDate();
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = [
                'name' => $dayNames[$i],
                'label' => $d->day . ' ' . $meses[$d->month - 1],
                'date' => $d->toDateString(),
                'highlight' => $d->toDateString() === $highlight,
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
            $orders = $this->partOrders($start, $end, $wc['name']);
            foreach (collect($this->partRows($start, $end, $wc['id']))->groupBy('part_id') as $partId => $records) {
                $state = $activity['part'][$wc['id'] . '|' . $partId] ?? null;
                $rows[] = $this->buildRow($records->all(), $columns) + [
                    'key' => "part:{$partId}",
                    'name' => $records->first()['part_number'],
                    'orders' => $orders[$records->first()['part_number']] ?? [],
                    'color' => null,
                    'href' => null,
                    'activity' => $state ? [
                        'state' => $state['live'] ? 'live' : 'last',
                        'time' => $this->timeLabel($state['live'] ? $state['start'] : $state['at']),
                        'items' => [$state + ['station' => '', 'startLabel' => $this->timeLabel($state['start']), 'endLabel' => $this->timeLabel($state['at'])]],
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
                    'activity' => $this->activityFor([$w['id']], $wcNames, $activity),
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
                    'activity' => $this->activityFor($catalogWcs->where('line_id', $l['id'])->pluck('id')->all(), $wcNames, $activity),
                ];
            }
        }
        $groups = array_values(array_filter($groups, fn ($g) => $g['rows']));
        $rowsByKey = array_column(array_merge([], ...array_column($groups, 'rows')), null, 'key');

        // Modal "Sin número de orden": total de la semana por estación y número de parte
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
            $key = 'line_progress:v4:no_order:' . $start->toDateString() . ':' . md5(implode(',', $wcIds) . '|' . $partId);
            $parts = $this->cached($key, $end, function () use ($wcIds, $start, $end, $partId) {
                $pivot = [];
                foreach (ProductionRecord::getNoOrderParts($wcIds, $start->toDateString(), $end->toDateString(), $partId) as $p) {
                    $k = $p->work_center_name . '|' . $p->part_number;
                    $pivot[$k] ??= ['station' => $p->work_center_name, 'number' => $p->part_number, 'name' => $p->part_name, 'total' => 0];
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
            // Botones de Ayuda visual y gráficas de prensa: solo estaciones de estampado (igual que /guest/wc-progress)
            'isStamping' => $wc && strtolower((string) ($catalog['lines'][$wc['line_id']]['area_name'] ?? '')) === 'estampado',
        ]);
    }
}
