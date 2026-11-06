<?php

namespace App\Livewire\Guest;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Producido por línea → estación → número de parte en un turno, sumando el
 * acumulado de histories dentro del horario del turno. Sin fecha en la URL se
 * sigue el turno actual (se refresca cada minuto); con fecha es histórico.
 */
class PartProgress extends Component
{
    private const CATALOG_TTL = 600;

    /** Archivo y no la base (CACHE_STORE=database): se lee en cada refresco */
    private const CACHE_STORE = 'file';

    /** Turno cerrado: sus cantidades ya no cambian */
    private const CLOSED_TTL = 604800;

    /** Margen tras el fin del turno para lecturas que llegan tarde, antes de darlo por cerrado */
    private const CLOSE_GRACE_MINUTES = 5;

    private const LIVE_STATES_TTL = 30;

    /** Turno histórico (Y-m-d + abreviatura). Vacíos = turno actual en vivo */
    #[Url(except: '')]
    public string $date = '';

    #[Url(except: '')]
    public string $shift = '';

    /** Ids de líneas y estaciones elegidas (multi select); vacío = todas. Sin tipo: ?line=11 llega como texto */
    #[Url(except: [])]
    public $line = [];

    #[Url(except: [])]
    public $wc = [];

    #[Url(except: '')]
    public string $part = '';

    /** Huella de los datos pintados: el refresco no re-renderiza si no cambió */
    #[Locked]
    public string $fingerprint = '';

    /** Deja los filtros como arreglos de ids aunque lleguen como texto (URL o petición alterada) */
    private function normalizeFilters(): void
    {
        $this->line = array_values(array_filter((array) $this->line, 'is_numeric'));
        $this->wc = array_values(array_filter((array) $this->wc, 'is_numeric'));
    }

    public function placeholder()
    {
        return view('livewire.guest.part-progress-placeholder');
    }

    /** Quita las estaciones que ya no pertenecen a las líneas elegidas */
    public function updatedLine(): void
    {
        $this->normalizeFilters();
        $allowed = $this->stations()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->wc = array_values(array_intersect($this->wc, $allowed));
    }

    /** Estaciones de las líneas elegidas (todas si no hay línea) */
    private function stations()
    {
        return collect($this->catalog())
            ->when($this->line !== [], fn ($c) => $c->whereIn('id', array_map('intval', $this->line)))
            ->pluck('workCenters')
            ->flatten(1)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function clearFilters(): void
    {
        $this->line = [];
        $this->wc = [];
        $this->part = '';
    }

    public function prevShift(): void
    {
        $this->moveShift(-1);
    }

    public function nextShift(): void
    {
        $this->moveShift(1);
    }

    public function goNow(): void
    {
        $this->date = '';
        $this->shift = '';
    }

    /** Turno anterior/siguiente; los turnos de un día van en orden de start_time (D, N) */
    private function moveShift(int $step): void
    {
        [$shift, $date] = $this->selected();
        $abbrs = array_column($this->shifts(), 'abbreviation');
        $i = array_search($shift['abbreviation'], $abbrs) + $step;

        if ($i < 0) {
            $date->subDay();
            $i = count($abbrs) - 1;
        } elseif ($i >= count($abbrs)) {
            $date->addDay();
            $i = 0;
        }

        [$current, $currentDate] = $this->current();
        if ($date->toDateString() === $currentDate->toDateString() && $abbrs[$i] === $current['abbreviation']) {
            $this->goNow();
            return;
        }
        // No se puede ir más allá del turno actual
        if ($date->gt($currentDate) || ($date->eq($currentDate) && $i > array_search($current['abbreviation'], $abbrs))) {
            return;
        }

        $this->date = $date->toDateString();
        $this->shift = $abbrs[$i];
    }

    private function shifts(): array
    {
        return Cache::remember('part_progress:v7:shifts', self::CATALOG_TTL, fn () => Shift::getAllShifts()
            ->map(fn ($s) => $s->only(['id', 'abbreviation', 'name', 'start_time', 'end_time']))
            ->all());
    }

    private function shiftByAbbr(string $abbr): ?array
    {
        return collect($this->shifts())->firstWhere('abbreviation', $abbr);
    }

    /** [turno, fecha de inicio del turno] actuales */
    private function current(): array
    {
        $info = Shift::getCurrentShiftInfo(now());

        return [$this->shiftByAbbr($info->shift?->abbreviation ?? '') ?? $this->shifts()[0], $info->date->copy()->startOfDay()];
    }

    /** [turno, fecha] mostrados: el de la URL si es válido, si no el actual */
    private function selected(): array
    {
        $shift = $this->shift !== '' ? $this->shiftByAbbr($this->shift) : null;

        if ($shift && $this->date !== '') {
            try {
                return [$shift, Carbon::createFromFormat('Y-m-d', $this->date)->startOfDay()];
            } catch (\Throwable) {
                // Fecha inválida en la URL: se muestra el turno actual
            }
        }

        return $this->current();
    }

    /** Líneas visibles con sus estaciones, para los filtros */
    private function catalog(): array
    {
        return Cache::remember('part_progress:v7:catalog', self::CATALOG_TTL, fn () => DB::table('lines')
            ->join('work_centers', 'work_centers.line_id', '=', 'lines.id')
            ->where('lines.show_in_progress', true)
            ->orderBy('lines.name')
            ->orderBy('work_centers.name')
            ->get(['lines.id AS line_id', 'lines.name AS line_name', 'lines.color', 'work_centers.id AS wc_id', 'work_centers.name AS wc_name'])
            ->groupBy('line_id')
            ->map(fn ($wcs) => [
                'id' => (int) $wcs[0]->line_id,
                'name' => $wcs[0]->line_name,
                'color' => $wcs[0]->color,
                'workCenters' => $wcs->map(fn ($w) => ['id' => (int) $w->wc_id, 'name' => $w->wc_name])->all(),
            ])
            ->values()
            ->all());
    }

    private function cache()
    {
        return Cache::store(self::CACHE_STORE);
    }

    /** El turno ya terminó (con margen): sus datos se guardan sin vencimiento corto */
    private function isClosed(object $range): bool
    {
        return $range->endDateTime->copy()->addMinutes(self::CLOSE_GRACE_MINUTES)->lte(now());
    }

    /**
     * [part_number_id => piezas] del acumulado de histories dentro del horario del turno.
     * Turno cerrado: una consulta y se guarda. Turno en curso: se guarda la suma con el
     * último id leído y en cada refresco solo se suman los registros nuevos (id > último).
     */
    private function produced(array $shift, Carbon $date): array
    {
        $range = Shift::getShiftDateTimeRange(Shift::make($shift), $date);
        $key = "part_progress:v8:produced:{$date->toDateString()}:{$shift['abbreviation']}";

        $sum = fn ($query) => $query
            ->where('created_at', '>=', $range->startDateTime->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $range->endDateTime->format('Y-m-d H:i:s'))
            ->groupBy('part_number_id')
            ->selectRaw('part_number_id, SUM(quantity) AS qty')
            ->pluck('qty', 'part_number_id')
            ->map(fn ($q) => (int) $q)
            ->all();

        if ($this->isClosed($range)) {
            return $this->cache()->remember($key, self::CLOSED_TTL, fn () => $sum(DB::table('histories')));
        }

        $cached = $this->cache()->get("{$key}:live");
        $lastId = (int) DB::table('histories')->max('id');

        if (!$cached) {
            $qty = $sum(DB::table('histories')->where('id', '<=', $lastId));
        } else {
            $qty = $cached['qty'];
            if ($lastId > $cached['lastId']) {
                foreach ($sum(DB::table('histories')->where('id', '>', $cached['lastId'])->where('id', '<=', $lastId)) as $partId => $q) {
                    $qty[$partId] = ($qty[$partId] ?? 0) + $q;
                }
            }
        }

        // shortcut: supone que histories solo recibe inserciones; si se editan o borran filas, el turno en curso no lo ve hasta que se cierra
        $this->cache()->put("{$key}:live", ['lastId' => $lastId, 'qty' => $qty], now()->addDay());

        return $qty;
    }

    /**
     * [part_number_id => 'live'|'stop'] de las órdenes del turno: "En progreso" gana sobre "Detenido".
     * En el turno en curso, "en proceso" es lo mismo que en / (LineProgress): un registro "En progreso"
     * con piezas en la última hora, sin importar en qué día o turno se planeó (una orden de ayer N
     * puede seguir corriendo hoy D y antes se veía Detenida).
     */
    private function states(array $shift, Carbon $date): array
    {
        $range = Shift::getShiftDateTimeRange(Shift::make($shift), $date);
        $closed = $this->isClosed($range);

        return $this->cache()->remember(
            "part_progress:v9:states:{$date->toDateString()}:{$shift['abbreviation']}",
            $closed ? self::CLOSED_TTL : self::LIVE_STATES_TTL,
            function () use ($shift, $date, $closed) {
                $states = DB::table('production_records')
                    ->join('statuses', 'statuses.id', '=', 'production_records.status_id')
                    ->where('production_records.planned_date', $date->toDateString())
                    ->where('production_records.shift_id', $shift['id'])
                    ->whereIn('statuses.name', ['En progreso', 'Detenido'])
                    ->groupBy('production_records.part_number_id')
                    ->selectRaw("production_records.part_number_id, MAX(CASE WHEN statuses.name = 'En progreso' THEN 1 ELSE 0 END) AS live")
                    ->pluck('live', 'part_number_id')
                    ->map(fn ($live) => $live ? 'live' : 'stop')
                    ->all();

                if ($closed) {
                    return $states;
                }

                $states = array_map(fn () => 'stop', $states);
                foreach (ProductionRecord::getWorkCenterActivity(Carbon::now(), LineProgress::LIVE_MINUTES) as $r) {
                    if ((int) $r->live === 1) {
                        $states[(int) $r->part_number_id] = 'live';
                    }
                }

                return $states;
            }
        );
    }

    /** [id => datos] de los números de parte (solo clases F1, M1–M4) de líneas visibles */
    private function parts(): array
    {
        return $this->cache()->remember('part_progress:v8:parts', self::CATALOG_TTL, fn () => ProductionRecord::applyProgressItemClasses(
            DB::table('part_numbers')
                ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
                ->join('lines', 'lines.id', '=', 'work_centers.line_id')
                ->leftJoin('item_classes', 'item_classes.id', '=', 'part_numbers.item_class_id')
                ->where('lines.show_in_progress', true)
        )
            ->get([
                'part_numbers.id', 'part_numbers.number', 'part_numbers.name',
                'item_classes.abbreviation AS item_class',
                'work_centers.id AS wc_id', 'work_centers.name AS wc_name',
                'lines.id AS line_id', 'lines.name AS line_name', 'lines.color AS line_color',
            ])
            ->keyBy('id')
            ->all());
    }

    /** Número de parte buscado con sus anteriores (componentes) y posteriores (donde se usa) */
    private function partDetail(string $number, array $produced, array $states): ?array
    {
        $part = PartNumber::query()->where('number', $number)->orderByDesc('id')->first();
        if (!$part) {
            return null;
        }

        $info = collect($this->parts());

        // Ramas: cada nodo cuelga del que lo produce/usa. Solo quedan los números de parte con orden en el turno
        // o producidos; uno en cero se conserva únicamente si más adelante en su rama hay alguno con actividad.
        // Los que no son F1/M1–M4 o no están en una línea visible se saltan y sus hijos suben un nivel.
        $walk = function (int $id, array $path, array $adj) use (&$walk, $info, $produced, $states): array {
            if (in_array($id, $path, true)) {
                return []; // ciclo en los datos de origen
            }
            $path[] = $id;
            $kids = collect(array_unique($adj[$id] ?? []))->flatMap(fn ($c) => $walk($c, $path, $adj))->values()->all();
            if (!$info->has($id)) {
                return $kids;
            }
            $row = $this->partRow($info[$id], $produced, $states);
            $active = $row['qty'] > 0 || isset($states[$id]);

            return $active || $kids ? [$row + ['active' => $active, 'children' => $kids]] : [];
        };
        $branches = function (string $dir) use ($walk, $part): array {
            $adj = $this->adjacency($part->id, $dir);

            return collect(array_unique($adj[$part->id] ?? []))->flatMap(fn ($c) => $walk($c, [$part->id], $adj))->values()->all();
        };
        $prev = $branches('prev');
        $next = $branches('next');

        return [
            'part' => $info->has($part->id)
                ? $this->partRow($info[$part->id], $produced, $states)
                // Clase fuera de F1/M1–M4 o sin estación: se muestra lo básico
                : ['id' => $part->id, 'number' => $part->number, 'name' => $part->name, 'item_class' => $part->itemClass?->abbreviation, 'wc_name' => $part->workCenter?->name, 'line_name' => null, 'qty' => $produced[$part->id] ?? 0, 'state' => $this->state($states, $part->id)],
            'prev' => $prev,
            'next' => $next,
            'prevCount' => self::countNodes($prev),
            'nextCount' => self::countNodes($next),
        ];
    }

    /**
     * Vecinos de cada número de parte hacia atrás ('prev', sus componentes) o hacia adelante ('next', dónde se usa).
     * Manda la lista de materiales (Infor MBM): el hijo siempre va antes que el padre. La clase NO indica el orden
     * (IIC: M1 Prensa, M2 Sub Assy, M3 Assy, M4 Pintura, F1 Terminado); p. ej. un "AFTER BUSH" M2 consume su
     * "AFTER PAINT" M4. Los fantasmas (01) se atraviesan en partDetail.
     *
     * @return array<int, int[]> id => ids de sus vecinos en esa dirección
     */
    private function adjacency(int $rootId, string $dir): array
    {
        return $this->cache()->remember("part_progress:v2:adj:{$dir}:{$rootId}", self::CATALOG_TTL, function () use ($rootId, $dir) {
            $adj = [];
            $seen = [$rootId => true];
            $frontier = [$rootId];
            [$atCol, $otherCol] = $dir === 'next' ? ['c', 'p'] : ['p', 'c'];

            for ($depth = 0; $frontier && $depth < 20; $depth++) {
                $edges = DB::table('part_number_relations AS r')
                    ->where('r.is_active', true)
                    ->whereIn($dir === 'next' ? 'r.child_part_number_id' : 'r.parent_part_number_id', $frontier)
                    ->orderBy('r.sequence_order')
                    ->get(['r.parent_part_number_id AS p', 'r.child_part_number_id AS c']);

                $next = [];
                foreach ($edges as $e) {
                    [$at, $other] = [(int) $e->$atCol, (int) $e->$otherCol];
                    if (!in_array($at, $frontier, true)) {
                        continue;
                    }
                    $adj[$at][] = $other;
                    if (!isset($seen[$other])) {
                        $seen[$other] = true;
                        $next[] = $other;
                    }
                }
                $frontier = $next;
            }

            return $adj;
        });
    }

    private static function countNodes(array $nodes): int
    {
        return array_sum(array_map(fn ($n) => 1 + self::countNodes($n['children']), $nodes));
    }

    /** Solo hay dos estados: "En progreso" = en proceso; cualquier otro (o sin orden en el turno) = detenido */
    private function state(array $states, int $partId): string
    {
        return ($states[$partId] ?? null) === 'live' ? 'live' : 'stop';
    }

    private function partRow(object $p, array $produced, array $states): array
    {
        return [
            'id' => (int) $p->id,
            'number' => $p->number,
            'name' => $p->name,
            'item_class' => $p->item_class,
            'wc_name' => $p->wc_name,
            'line_name' => $p->line_name,
            'qty' => $produced[$p->id] ?? 0,
            'state' => $this->state($states, $p->id),
        ];
    }

    /** Refresco de cada minuto: si los datos no cambiaron no se vuelve a pintar ni a enviar la página */
    public function poll(): void
    {
        [$shift, $date] = $this->selected();

        if (md5(serialize([$this->produced($shift, $date), $this->states($shift, $date)])) === $this->fingerprint) {
            $this->skipRender();
        }
    }

    public function render()
    {
        $this->normalizeFilters();
        [$shift, $date] = $this->selected();
        $isLive = $this->date === '' || $this->shift === '';
        $search = trim($this->part);

        $produced = $this->produced($shift, $date);
        $states = $this->states($shift, $date);
        $this->fingerprint = md5(serialize([$produced, $states]));

        $lineIds = array_map('intval', $this->line);
        $wcIds = array_map('intval', $this->wc);
        $rows = collect(array_intersect_key($this->parts(), $produced + $states))
            ->filter(fn ($r) => ($lineIds === [] || in_array((int) $r->line_id, $lineIds, true))
                && ($wcIds === [] || in_array((int) $r->wc_id, $wcIds, true))
                && ($search === '' || stripos($r->number, $search) !== false))
            ->values();

        // línea → estación → partes (en proceso primero, luego por cantidad)
        $lines = $rows->map(fn ($r) => $this->partRow($r, $produced, $states) + ['line_id' => (int) $r->line_id, 'line_color' => $r->line_color, 'wc_id' => (int) $r->wc_id])
            ->groupBy('line_id')
            ->map(fn ($parts) => [
                'id' => $parts[0]['line_id'],
                'name' => $parts[0]['line_name'],
                'color' => $parts[0]['line_color'],
                'total' => $parts->sum('qty'),
                'stations' => $parts->groupBy('wc_id')
                    ->map(fn ($ps) => [
                        'id' => $ps[0]['wc_id'],
                        'name' => $ps[0]['wc_name'],
                        'total' => $ps->sum('qty'),
                        'parts' => $ps->sortBy([fn ($a, $b) => ($b['state'] === 'live') <=> ($a['state'] === 'live'), ['qty', 'desc']])->values()->all(),
                    ])
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all(),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('livewire.guest.part-progress', [
            'lines' => $lines,
            'search' => $search,
            'lineOptions' => collect($this->catalog())->map(fn ($l) => ['value' => $l['id'], 'label' => $l['name']])->all(),
            'stationOptions' => $this->stations()->map(fn ($w) => ['value' => $w['id'], 'label' => $w['name']])->all(),
            // Detalle con número exacto, o con el único que coincide con la búsqueda
            'detail' => $search === '' ? null : $this->partDetail($rows->count() === 1 ? $rows[0]->number : $search, $produced, $states),
            'shiftName' => $shift['name'],
            'shiftHours' => substr($shift['start_time'], 0, 5) . '–' . substr($shift['end_time'], 0, 5),
            'dateLabel' => $date->locale('es')->isoFormat('ddd D MMM YYYY'),
            'isLive' => $isLive,
        ]);
    }
}
