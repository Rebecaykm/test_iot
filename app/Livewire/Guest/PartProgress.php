<?php

namespace App\Livewire\Guest;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Avance semanal por línea → estación → número de parte, con los días divididos
 * por turno. Junta / (por estación) y /guest/wc-progress/{estación} (por parte).
 *
 * Para que sea fluida con toda la planta (~900 partes × 14 celdas):
 *  1. Datos por versión: una consulta barata (checksum por número de parte) dice qué
 *     cambió. Si nada, no se consulta ni se arma nada. Si sí, solo se vuelven a
 *     sumar esas partes; lo demás (lo ya completado) se reusa.
 *  2. Refresco por renglones: el poll manda solo los <tr> que cambiaron y el navegador
 *     los reemplaza; la tabla completa solo viaja al cambiar de semana o de filtro.
 *  3. Carga diferida: la página aparece de inmediato y la tabla llega después.
 */
class PartProgress extends Component
{
    private const CACHE_STORE = 'file';

    /** Consulta de versión: compartida entre pantallas, a lo más 5 s de retraso */
    private const VERSION_TTL = 5;

    /** Datos y vista armada: se reusan mientras la versión no cambie (el TTL solo limpia) */
    private const DATA_TTL = 1800;

    private const CATALOG_TTL = 600;

    /** Versiones anteriores de la vista que se recuerdan para mandar solo lo que cambió */
    private const PREV_VERSIONS = 5;

    /** Más partes modificadas que esto: conviene recalcular la semana completa */
    private const MAX_DELTA_PARTS = 500;

    private const MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /** Lunes de la semana mostrada (Y-m-d) */
    public string $weekStart = '';

    /** Número de parte buscado: se muestran él, sus componentes y sus procesos siguientes. Va en la URL (?part=). */
    #[Url(as: 'part', except: '')]
    public string $partNumber = '';

    /** Texto del buscador (se aplica al presionar Enter / Buscar) */
    public string $partInput = '';

    /** Hash de la vista que tiene la pantalla: el refresco manda solo lo que cambió desde ella */
    #[Locked]
    public string $renderedHash = '';

    public function mount(): void
    {
        $this->weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->partNumber = strtoupper(trim($this->partNumber));
        $this->partInput = $this->partNumber;
    }

    public function placeholder()
    {
        return view('livewire.guest.part-progress-placeholder');
    }

    public function applyPart(): void
    {
        $this->partNumber = strtoupper(trim($this->partInput));
        $this->partInput = $this->partNumber;
    }

    public function clearPart(): void
    {
        $this->partNumber = '';
        $this->partInput = '';
    }

    public function prevWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    private function store()
    {
        return Cache::store(self::CACHE_STORE);
    }

    /** Arma la caché una sola vez aunque varias pantallas la pidan al mismo tiempo */
    private function rebuildOnce(string $key, callable $isFresh, callable $build): array
    {
        return $this->store()->lock("{$key}:lock", 30)->block(20, function () use ($key, $isFresh, $build) {
            $cached = $this->store()->get($key);
            if ($cached && $isFresh($cached)) {
                return $cached;
            }

            $value = $build($cached);
            $this->store()->put($key, $value, self::DATA_TTL);

            return $value;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Datos                                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Huella de los registros de la semana por número de parte (~50 ms): checksum de
     * las columnas que se muestran + conteo + suma producida. No se usa updated_at
     * porque el conteo de piezas (IoT) escribe directo en la base sin actualizarlo.
     * Devuelve ['hash' => huella de toda la semana, 'parts' => [part_id => huella]].
     */
    private function queryVersion(Carbon $start, Carbon $end): array
    {
        $parts = DB::table('production_records')
            ->whereBetween('planned_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('part_number_id')
            ->selectRaw('part_number_id AS p, CHECKSUM_AGG(BINARY_CHECKSUM(id, planned_date, shift_id, shop_order_number, planned_quantity, produced_quantity, status_id)) AS k, COUNT(*) AS c, SUM(produced_quantity) AS pr')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->p => "{$r->k}:{$r->c}:{$r->pr}"])
            ->all();

        ksort($parts);

        return ['hash' => md5(serialize($parts)), 'parts' => $parts];
    }

    private function dataVersion(Carbon $start, Carbon $end): array
    {
        return $this->store()->remember(
            "part_progress:v5:version:{$start->toDateString()}",
            self::VERSION_TTL,
            fn () => $this->queryVersion($start, $end)
        );
    }

    /** Filas de la consulta como arreglos simples (serializados pesan ~10 veces menos que modelos) */
    private function mapRows(Collection $records): array
    {
        return $records->map(fn ($r) => [
            'area' => $r->area_name ?: 'Sin área',
            'line_id' => (int) $r->line_id,
            'line_name' => $r->line_name,
            'line_color' => $r->line_color,
            'wc_id' => (int) $r->work_center_id,
            'wc_name' => $r->work_center_name,
            'part_id' => (int) $r->part_number_id,
            'part' => $r->part_number,
            'item_class' => $r->item_class,
            'shift' => $r->shift,
            'date' => Carbon::parse($r->planned_date)->toDateString(),
            'plan' => (int) $r->plan,
            'real' => (int) $r->with_order,
            'no_order' => (int) $r->no_order,
            'in_progress' => (int) $r->in_progress > 0,
        ])->all();
    }

    /**
     * Totales de toda la planta por parte/turno/día para la semana, al día con la
     * versión actual. Se recalcula solo cuando la versión cambia, y de ser posible
     * solo las partes modificadas.
     */
    private function weekData(Carbon $start, Carbon $end): array
    {
        $key = "part_progress:v5:week:{$start->toDateString()}";
        $version = $this->dataVersion($start, $end);

        $data = $this->store()->get($key);
        if ($data && $data['version'] === $version['hash']) {
            return $data;
        }

        return $this->rebuildOnce($key, fn ($d) => $d['version'] === $version['hash'], function ($old) use ($start, $end) {
            // Huella fresca (sin caché) justo antes de recalcular
            $version = $this->queryVersion($start, $end);
            $this->store()->put("part_progress:v5:version:{$start->toDateString()}", $version, self::VERSION_TTL);

            return ($old ? $this->applyDelta($old, $version, $start, $end) : null)
                ?? [
                    'version' => $version['hash'],
                    'parts' => $version['parts'],
                    'rows' => $this->mapRows(ProductionRecord::getPartWeeklyProgress($start->toDateString(), $end->toDateString())),
                ];
        });
    }

    /**
     * Vuelve a sumar solo las partes cuya huella cambió (producción, plan, estatus,
     * registros nuevos o borrados); lo demás, como lo ya completado, se reusa.
     * null = demasiados cambios, conviene recalcular la semana completa.
     */
    private function applyDelta(array $old, array $version, Carbon $start, Carbon $end): ?array
    {
        $partIds = [];
        foreach ($version['parts'] + $old['parts'] as $partId => $_) {
            if (($old['parts'][$partId] ?? null) !== ($version['parts'][$partId] ?? null)) {
                $partIds[] = $partId;
            }
        }

        if (count($partIds) > self::MAX_DELTA_PARTS) {
            return null;
        }

        $changed = array_flip($partIds);
        $rows = array_filter($old['rows'], fn ($r) => !isset($changed[$r['part_id']]));

        if (!empty($partIds)) {
            $fresh = ProductionRecord::getPartWeeklyProgress($start->toDateString(), $end->toDateString(), $partIds);
            array_push($rows, ...$this->mapRows($fresh));
        }

        return ['version' => $version['hash'], 'parts' => $version['parts'], 'rows' => array_values($rows)];
    }

    private function shifts(): array
    {
        return $this->store()->remember('line_progress:shifts', self::CATALOG_TTL, fn () => Shift::getAllShifts()
            ->map(fn ($s) => ['abbr' => $s->abbreviation, 'name' => $s->name])
            ->values()
            ->all());
    }

    /**
     * Familia del número de parte buscado (él + componentes + procesos siguientes,
     * multinivel, desde part_number_relations), con su estación y línea para poder
     * mostrar también las partes que no tienen plan ni producción en la semana.
     * null = el número de parte no existe.
     */
    private function partFamily(string $number): ?array
    {
        return $this->store()->remember("part_progress:v2:family:{$number}", self::CATALOG_TTL, function () use ($number) {
            $part = PartNumber::query()->where('number', $number)->first();
            if (!$part) {
                return null;
            }

            $ids = collect($part->getFullPreviousChain())
                ->merge($part->getFullNextChain())
                ->pluck('part_number_id')
                ->push($part->id)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $parts = ProductionRecord::applyProgressItemClasses(
                PartNumber::query()
                    ->select('part_numbers.id', 'part_numbers.number', 'part_numbers.name', 'part_numbers.work_center_id', 'part_numbers.item_class_id')
                    ->whereIn('part_numbers.id', $ids)
                    ->whereNotNull('part_numbers.work_center_id')
            )
                ->with(['workCenter.line.area', 'itemClass'])
                ->get()
                ->filter(fn ($p) => $p->workCenter?->line)
                ->map(fn ($p) => [
                    'area' => $p->workCenter->line->area->name ?? 'Sin área',
                    'line_id' => (int) $p->workCenter->line->id,
                    'line_name' => $p->workCenter->line->name,
                    'line_color' => $p->workCenter->line->color,
                    'wc_id' => (int) $p->workCenter->id,
                    'wc_name' => $p->workCenter->name,
                    'part_id' => (int) $p->id,
                    'part' => $p->number,
                    'item_class' => $p->itemClass?->abbreviation,
                ])
                ->values()
                ->all();

            return [
                'id' => (int) $part->id,
                'number' => $part->number,
                'name' => $part->name,
                'ids' => $ids->all(),
                'parts' => $parts,
            ];
        });
    }

    /* ------------------------------------------------------------------ */
    /* Vista                                                               */
    /* ------------------------------------------------------------------ */

    /** Encabezados de los 7 días (lunes a domingo) */
    private function days(Carbon $start): array
    {
        $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = [
                'name' => $dayNames[$i],
                'label' => $d->day . ' ' . self::MONTHS[$d->month - 1],
                'date' => $d->toDateString(),
                'isToday' => $d->isToday(),
            ];
        }

        return $days;
    }

    /**
     * Celdas de día/turno de un renglón, como HTML ya escapado. Se arman aquí y no
     * con @if/@foreach en Blade porque Livewire agrega marcadores de comentario a
     * cada directiva: con ~900 partes × 14 celdas el HTML pasaba de 4 MB por refresco.
     * Barra con el estilo de /guest/wc-progress: plan = contorno gris (el azul se perdía con el verde) y real =
     * contorno verde, los dos con relleno claro; real adentro a la izquierda, plan a la derecha.
     */
    private function cellsHtml(array $cells, array $columns, bool $sep): string
    {
        $html = '';
        foreach ($columns as $col) {
            $class = ($col['first'] ? 'lp-td-day' : 'pp-td-shift') . ($col['isToday'] ? ' pp-today' : '') . ($sep ? ' lp-td-sep' : '');
            $c = $cells[$col['abbr']][$col['date']] ?? null;

            // Vacía (lo sin orden va solo en la última columna): el guion lo pone el CSS (.pp-x), un solo nodo por celda
            if (!$c || ($c['plan'] === 0 && $c['real'] === 0 && !$c['inProgress'])) {
                $html .= "<td class=\"{$class} pp-x\"></td>";
                continue;
            }

            $title = $col['title'] . ' · Plan ' . number_format($c['plan']) . ' · Real ' . number_format($c['real'])
                . ($c['percent'] !== null ? " ({$c['percent']}%)" : '')
                . ($c['noOrder'] ? ' · Sin orden ' . number_format($c['noOrder']) : '')
                . ($c['inProgress'] ? ' · En Proceso' : '');

            $real = '<span class="pp-real' . ($c['plan'] > 0 && $c['real'] >= $c['plan'] ? ' pp-done' : '') . '">' . number_format($c['real']) . '</span>';

            $html .= "<td class=\"{$class}\"><div class=\"pp-cell" . ($c['inProgress'] ? ' pp-live' : '') . '" title="' . e($title) . '">'
                . ($c['inProgress'] ? '<span class="lp-dot"></span>' : '')
                . ($c['plan'] > 0
                    ? '<div class="pp-bar">'
                        . ($c['fill'] > 0 ? "<div class=\"pp-fill\" style=\"width:calc({$c['fill']}% + 2px)\"></div>" : '')
                        . '<div class="pp-bar-txt">' . $real . '<span class="pp-plan">/ ' . number_format($c['plan']) . '</span></div></div>'
                    : '<div class="pp-nums">' . $real . '<span class="pp-plan">Sin plan</span></div>')
                . '</div></td>';
        }

        return $html;
    }

    /**
     * Renglón completo (<tr> con id) de un número de parte: es la unidad que el
     * refresco reemplaza en el navegador. El primero de la estación lleva la celda
     * de la estación (rowspan); el último, el borde que separa estaciones.
     */
    private function rowHtml(array $wc, array $part, int $j, int $count, bool $isHit, array $columns): string
    {
        $sep = $j > 0 ? ' lp-td-sep' : '';
        $number = e($part['number']);

        return '<tr id="' . $part['rowId'] . '"' . ($j === $count - 1 ? ' class="border-b border-zinc-200 dark:border-zinc-800"' : '') . '>'
            . ($j === 0
                ? '<td rowspan="' . $count . '" class="pp-wc"><a href="' . e(route('guest.work-center-progress', $wc['name'], false)) . '" class="lp-wc-link" title="Ver detalle de ' . e($wc['name']) . '">' . e($wc['name']) . '</a></td>'
                : '')
            . '<td class="pp-part' . ($isHit ? ' pp-part-hit' : '') . $sep . '" title="' . $number . '"><div class="truncate">' . $number . '</div>'
            . ($part['inProgress'] ? '<span class="lp-wc-live"><span class="lp-dot"></span>En Proceso</span>' : '') . '</td>'
            . '<td class="pp-class' . $sep . '">' . e($part['itemClass'] ?? '–') . '</td>'
            . $this->cellsHtml($part['cells'], $columns, $j > 0)
            . '<td class="lp-td-day px-1.5 text-center' . $sep . ($part['noOrder'] > 0 ? '' : ' pp-x') . '">'
            . ($part['noOrder'] > 0 ? '<div class="pp-noorder" title="Producido sin orden en la semana">' . number_format($part['noOrder']) . '</div>' : '')
            . '</td></tr>';
    }

    /** Arma la vista (estructura + HTML por renglón) a partir de los datos de la planta */
    private function buildView(array $rows, Carbon $start): array
    {
        $days = $this->days($start);
        $shifts = $this->shifts();
        $rows = collect($rows);

        // Columnas de datos: cada día dividido en sus turnos
        $columns = [];
        foreach ($days as $day) {
            foreach ($shifts as $si => $shift) {
                $columns[] = [
                    'date' => $day['date'],
                    'abbr' => $shift['abbr'],
                    'first' => $si === 0,
                    'isToday' => $day['isToday'],
                    'title' => $shift['name'] . ' ' . $day['label'],
                ];
            }
        }

        // Filtro por número de parte: solo su familia; sus partes sin datos en la semana van con celdas vacías
        $family = null;
        $notFound = false;
        if ($this->partNumber !== '') {
            $family = $this->partFamily($this->partNumber);
            if ($family) {
                $rows = $rows->whereIn('part_id', $family['ids'])->concat($family['parts']);
            } else {
                $notFound = true;
                $rows = collect();
            }
        }

        // Agrupar: área => línea => estación => parte => turno => fecha => totales
        $map = [];
        foreach ($rows as $r) {
            $line = &$map[$r['area']][$r['line_id']];
            $line['name'] = $r['line_name'];
            $line['color'] = $r['line_color'];

            $wc = &$line['workCenters'][$r['wc_name']];
            $wc['id'] = $r['wc_id'];

            $part = &$wc['parts'][$r['part']];
            $part['id'] = $r['part_id'];
            $part['itemClass'] = $r['item_class'] ?? null;
            $part['cells'] ??= [];
            $part['noOrder'] = ($part['noOrder'] ?? 0) + ($r['no_order'] ?? 0);
            $part['inProgress'] = ($part['inProgress'] ?? false) || ($r['in_progress'] ?? false);

            if (isset($r['shift'])) {
                $plan = $r['plan'];
                $real = $r['real'];
                $part['cells'][$r['shift']][$r['date']] = [
                    'plan' => $plan,
                    'real' => $real,
                    'noOrder' => $r['no_order'],
                    'inProgress' => $r['in_progress'],
                    // Relleno verde de la barra (0–100) sobre el plan
                    'fill' => $plan > 0 ? min(100, (int) round($real / $plan * 100)) : ($real > 0 ? 100 : 0),
                    'percent' => $plan > 0 ? (int) round($real / $plan * 100) : null,
                ];
            }
            unset($line, $wc, $part);
        }

        ksort($map, SORT_NATURAL | SORT_FLAG_CASE);
        $areas = [];
        $rowsHtml = [];
        foreach ($map as $areaName => $lines) {
            uasort($lines, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

            $areaLines = [];
            foreach ($lines as $lineId => $line) {
                $workCenters = $line['workCenters'];
                uksort($workCenters, 'strnatcasecmp');

                $rowIds = [];
                foreach ($workCenters as $wcName => $wc) {
                    $parts = $wc['parts'];
                    uksort($parts, 'strnatcasecmp');

                    $j = 0;
                    foreach ($parts as $number => $p) {
                        $p['number'] = (string) $number;
                        $p['rowId'] = "ppr-{$wc['id']}-{$p['id']}";
                        $rowsHtml[$p['rowId']] = $this->rowHtml(
                            ['id' => $wc['id'], 'name' => $wcName],
                            $p,
                            $j++,
                            count($parts),
                            $family && $p['id'] === $family['id'],
                            $columns
                        );
                        $rowIds[] = $p['rowId'];
                    }
                }

                $areaLines[] = ['id' => $lineId, 'name' => $line['name'], 'color' => $line['color'], 'rows' => $rowIds];
            }

            $areas[] = ['name' => $areaName, 'lines' => $areaLines];
        }

        $rowHashes = array_map('md5', $rowsHtml);

        return [
            'areas' => $areas,
            'rowsHtml' => $rowsHtml,
            'rowHashes' => $rowHashes,
            'hash' => md5(serialize([$areas, $rowHashes])),
            'colCount' => count($columns) + 4,
            'family' => $family ? ['number' => $family['number'], 'name' => $family['name'], 'count' => count($family['parts'])] : null,
            'notFound' => $notFound,
        ];
    }

    /** Llave de la vista: semana + día de hoy (a medianoche cambia la columna resaltada) + filtro */
    private function viewKey(string $kind): string
    {
        return "part_progress:v5:{$kind}:{$this->weekStart}:" . Carbon::today()->toDateString() . ':' . md5($this->partNumber);
    }

    /** Vista armada para la semana y el filtro mostrados, al día con la versión de los datos */
    private function payload(): array
    {
        $start = Carbon::parse($this->weekStart)->startOfDay();
        $end = $start->copy()->addDays(6); // domingo

        $data = $this->weekData($start, $end);
        $key = $this->viewKey('view');

        $view = $this->store()->get($key);
        if ($view && $view['version'] === $data['version']) {
            return $view;
        }

        return $this->rebuildOnce($key, fn ($v) => $v['version'] === $data['version'], function () use ($data, $start) {
            $view = $this->buildView($data['rows'], $start) + ['version' => $data['version']];

            // Hashes por renglón de las últimas versiones: con ellos el refresco sabe qué renglones mandar
            $prevKey = $this->viewKey('prev');
            $prev = $this->store()->get($prevKey, []);
            $prev[$view['hash']] = $view['rowHashes'];
            $this->store()->put($prevKey, array_slice($prev, -self::PREV_VERSIONS, null, true), self::DATA_TTL);

            return $view;
        });
    }

    /**
     * Refresco automático:
     *  - sin cambios: no se re-renderiza nada (solo la hora del pie)
     *  - mismos renglones con otros valores: se mandan solo los <tr> que cambiaron
     *  - cambió la estructura (partes nuevas, otro día): render completo
     */
    public function poll(): void
    {
        $view = $this->payload();
        $time = Carbon::now()->format('H:i:s');

        if ($view['hash'] === $this->renderedHash) {
            $this->skipRender();
            $this->dispatch('part-progress-checked', time: $time);
            return;
        }

        $prev = $this->store()->get($this->viewKey('prev'), [])[$this->renderedHash] ?? null;

        if ($prev !== null && array_keys($prev) === array_keys($view['rowHashes'])) {
            $changed = [];
            foreach ($view['rowHashes'] as $id => $hash) {
                if ($prev[$id] !== $hash) {
                    $changed[$id] = $view['rowsHtml'][$id];
                }
            }

            $this->renderedHash = $view['hash'];
            $this->skipRender();
            $this->dispatch('part-progress-patch', rows: $changed, time: $time);
            return;
        }

        $this->dispatch('part-progress-checked', time: $time);
    }

    public function render()
    {
        $start = Carbon::parse($this->weekStart)->startOfDay();
        $end = $start->copy()->addDays(6);

        $view = $this->payload();
        $this->renderedHash = $view['hash'];

        $currentWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        return view('livewire.guest.part-progress', [
            'days' => $this->days($start),
            'shifts' => $this->shifts(),
            'view' => $view,
            'family' => $view['family'],
            'notFound' => $view['notFound'],
            'weekLabel' => $start->day . ' ' . self::MONTHS[$start->month - 1] . ' – ' . $end->day . ' ' . self::MONTHS[$end->month - 1] . ' ' . $end->year,
            'isCurrentWeek' => $this->weekStart === $currentWeekStart,
            'lastUpdated' => Carbon::now()->format('H:i:s'),
        ]);
    }
}
