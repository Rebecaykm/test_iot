<?php

namespace App\Livewire;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ShiftProductionTimeline extends Component
{
    public string $chartId;
    public $workCenter;

    /** Etiquetas del eje Y: número de parte por barra */
    public array $labels = [];

    /** Rango de cada barra REAL en horas-desde-el-inicio-de-la-línea: [{x:[ini,fin], y:parte}, ...] */
    public array $ranges = [];

    /** Cantidad producida por barra real (tooltip) */
    public array $quantities = [];

    /** Texto "HH:mm - HH:mm" por barra real (tooltip) */
    public array $timeLabels = [];

    /** Rango de cada barra de PLAN (azul): [{x:[ini,fin], y:parte}, ...] */
    public array $planRanges = [];

    /** Cantidad planeada por barra de plan (tooltip) */
    public array $planQuantities = [];

    /** Texto "HH:mm - HH:mm" (ventana planeada completa) por barra de plan (tooltip) */
    public array $planTimeLabels = [];

    /** True si el work center pertenece al área de estampado (agrupa/ordena por troquel MID) */
    public bool $isStamping = false;

    /** Ancho total del eje X en horas (turno anterior + actual) */
    public float $durationHours = 12;

    /** Inicio de la línea de tiempo en ISO (inicio del turno seleccionado) */
    public ?string $shiftStartIso = null;

    /** Fecha seleccionada (Y-m-d). Por defecto la del turno actual */
    public string $selectedDate = '';

    /** Turno seleccionado. Por defecto el turno actual */
    public ?int $selectedShiftId = null;

    /** Opciones del select de turnos [['id'=>, 'label'=>], ...] */
    public array $shiftOptions = [];

    /** True solo cuando se está viendo el turno actual en vivo (controla el auto-refresco) */
    public bool $isLive = true;

    /** Hora (HH:MM:SS) de la última actualización de los datos */
    public ?string $lastUpdated = null;

    public function mount($workCenter): void
    {
        $this->chartId = 'timeline_' . Str::random(10);
        $this->workCenter = $workCenter;

        // ¿El área es estampado? (afecta cómo se agrupan/ordenan las barras por MID)
        $wc = WorkCenter::with('line.area')->where('name', $workCenter)->first();
        $this->isStamping = $wc && $wc->line && $wc->line->area
            && strtolower($wc->line->area->name) === 'estampado';

        // Catálogo de turnos para el select
        $this->shiftOptions = Shift::getAllShifts()
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->name . ' (' . substr($s->start_time, 0, 5) . ' - ' . substr($s->end_time, 0, 5) . ')',
            ])
            ->values()
            ->all();

        // Por defecto: turno actual y su fecha
        $now = Carbon::now();
        $current = Shift::getCurrentShiftInfo($now);
        $this->selectedShiftId = $current->shift?->id ?? ($this->shiftOptions[0]['id'] ?? null);
        $this->selectedDate = ($current->date ?? $now)->toDateString();

        $this->refreshTimeline();
    }

    /** Recargar al cambiar la fecha o el turno desde la vista */
    public function updatedSelectedDate(): void
    {
        $this->refreshTimeline();
    }

    public function updatedSelectedShiftId(): void
    {
        $this->refreshTimeline();
    }

    #[On('refresh-timeline')]
    public function refreshTimeline(): void
    {
        $now = Carbon::now();
        $this->lastUpdated = $now->format('H:i:s');

        $this->labels = [];
        $this->ranges = [];
        $this->quantities = [];
        $this->timeLabels = [];
        $this->planRanges = [];
        $this->planQuantities = [];
        $this->planTimeLabels = [];

        $shift = $this->selectedShiftId ? Shift::find($this->selectedShiftId) : null;
        $date = $this->selectedDate ? Carbon::parse($this->selectedDate) : null;
        $range = ($shift && $date) ? Shift::getShiftDateTimeRange($shift, $date) : null;

        // Selección inválida: mandar todo vacío
        if (!$range) {
            $this->isLive = false;
            $this->dispatchTimeline();
            return;
        }

        $baseStart = $range->startDateTime->copy();
        $timelineEnd = $range->endDateTime->copy();

        $this->shiftStartIso = $baseStart->toIso8601String();
        $this->durationHours = round(abs($baseStart->diffInMinutes($timelineEnd)) / 60, 2);

        // ¿Es el turno actual en vivo? (controla si se sigue auto-refrescando)
        $current = Shift::getCurrentShiftInfo($now);
        $this->isLive = $current->shift
            && $current->shift->id === $shift->id
            && $current->date->toDateString() === $date->toDateString();

        $records = ProductionRecord::getShiftProductionTimeline(
            $this->workCenter,
            $shift->id,
            $date
        );

        // Filas = solo las partes que tuvieron cantidad planeada o producida en el turno.
        $planParts = ProductionRecord::getShiftPlannedSchedule(
            $this->workCenter,
            $shift->id,
            $date
        );

        $this->buildBars($records, $planParts, $baseStart, $timelineEnd, $now);

        $this->dispatchTimeline();
    }

    /**
     * Construye los segmentos de la línea de tiempo. Cada número de parte ocupa
     * UNA sola fila (no se repite), pero conserva un segmento por cada registro con
     * su propio inicio y fin. Así un material producido de 01:00 a 02:00 y luego de
     * 10:00 a 13:00 se muestra como dos barras en la misma fila.
     */
    private function buildBars($records, $planParts, Carbon $baseStart, Carbon $timelineEnd, Carbon $now): void
    {
        // MID (troquel) por parte de estampado: se usa para ordenar y para agrupar
        // en paralelo cuando no hay production_order.
        $midByPart = [];
        if ($this->isStamping) {
            $stampParts = PartNumber::whereIn('id', $planParts->pluck('part_number_id')->all())
                ->with(['customAttributes' => fn ($q) => $q->where('key', 'mid')])
                ->get();
            foreach ($stampParts as $p) {
                $attrs = $p->customAttributes->keyBy('key');
                $midByPart[$p->id] = (string) ($attrs['mid']->value ?? '');
            }
        }

        // Orden de las filas (eje Y): por production_order; si una parte no tiene orden
        // (o empatan), se desempata por MID ascendente en estampado, y por número de
        // parte ascendente en lo demás.
        $planParts = $planParts->sort(function ($a, $b) use ($midByPart) {
            $ao = is_null($a->production_order) ? PHP_INT_MAX : (int) $a->production_order;
            $bo = is_null($b->production_order) ? PHP_INT_MAX : (int) $b->production_order;
            if ($ao !== $bo) {
                return $ao <=> $bo;
            }
            if ($this->isStamping) {
                $cmp = strcmp($midByPart[$a->part_number_id] ?? '', $midByPart[$b->part_number_id] ?? '');
                if ($cmp !== 0) {
                    return $cmp;
                }
            }
            return strcmp((string) $a->part_number, (string) $b->part_number);
        })->values();

        // Categorías (eje Y): un número de parte por fila, en orden de producción
        $this->labels = $planParts->pluck('part_number')->all();

        // ----- Barras REALES (verde): un segmento por registro iniciado -----
        $segments = [];
        foreach ($records as $record) {
            $start = Carbon::parse($record->production_start);
            $inProgress = empty($record->production_end);
            $end = $inProgress ? $now->copy() : Carbon::parse($record->production_end);

            // Acotar a la ventana visible de la línea de tiempo
            if ($start->lt($baseStart)) {
                $start = $baseStart->copy();
            }
            if ($end->gt($timelineEnd)) {
                $end = $timelineEnd->copy();
            }

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $segments[] = [
                'part' => $record->part_number,
                'start' => $start,
                'end' => $end,
                'quantity' => (int) $record->produced_quantity,
                'inProgress' => $inProgress,
            ];
        }

        // Segmentos ordenados cronológicamente
        usort($segments, fn ($a, $b) => $a['start']->getTimestamp() <=> $b['start']->getTimestamp());

        foreach ($segments as $segment) {
            $startOffset = round(abs($baseStart->diffInMinutes($segment['start'])) / 60, 3);
            $endOffset = round(abs($baseStart->diffInMinutes($segment['end'])) / 60, 3);

            // {x:[inicio,fin], y:número de parte} => varios segmentos pueden ir en la misma fila
            $this->ranges[] = ['x' => [$startOffset, $endOffset], 'y' => $segment['part']];
            $this->quantities[] = $segment['quantity'];
            $this->timeLabels[] = $segment['start']->format('d-m H:i') . ' - '
                . ($segment['inProgress'] ? 'en proceso' : $segment['end']->format('d-m H:i'));
        }

        // ----- Barras de PLAN (azul): secuenciadas por production_order -----
        $this->buildPlanBars($planParts, $baseStart, $now, $midByPart);
    }

    /**
     * Construye la barra de plan de cada número de parte siguiendo el production_order.
     *
     * Las partes se agrupan en PARALELO (mismo arranque; el reloj del plan no avanza
     * hasta que termina la barra más larga del grupo) cuando:
     *   - comparten production_order, o
     *   - en estampado sin production_order, comparten el mismo MID (troquel): salen
     *     en el mismo golpe, así que van a la misma hora pero cada una en su fila.
     * Lo demás va en secuencia. La primera arranca al inicio del turno. El largo de
     * cada barra es el tiempo para producir su cantidad planeada según production_rate
     * (piezas/hora). La barra sólo se pinta hasta "ahora": no se marca producción
     * planeada en el futuro.
     */
    private function buildPlanBars($planParts, Carbon $baseStart, Carbon $now, array $midByPart): void
    {
        // Clave de agrupación en paralelo de cada parte (ver doc del método).
        $groupKey = function ($part) use ($midByPart) {
            if (!is_null($part->production_order)) {
                return 'ord:' . (int) $part->production_order;
            }
            if ($this->isStamping && ($midByPart[$part->part_number_id] ?? '') !== '') {
                return 'mid:' . $midByPart[$part->part_number_id];
            }
            return 'solo:' . $part->part_number_id; // única => va sola (en secuencia)
        };

        // "Ahora" en horas desde el inicio del turno, acotado a la duración del turno.
        // Turno pasado: nowOffset > durationHours => se muestra el plan completo.
        $nowOffset = ($now->getTimestamp() - $baseStart->getTimestamp()) / 3600;
        $cap = min($nowOffset, $this->durationHours);

        // Tiempo necesario (horas) para producir la cantidad planeada de una parte.
        // production_rate ya viene en piezas/hora en todas las áreas (antes, en
        // estampado, era SPM y había que convertir con *60*pieces_per_shot).
        // En estampado se usa tal cual; fuera de estampado se ajusta por eficiencia,
        // igual que en /production-dashboard.
        $durationFor = function ($part) {
            $plannedQty = (int) $part->planned_quantity;
            $rate = (float) $part->production_rate;

            if ($this->isStamping) {
                $ratePerHour = $rate;
            } else {
                $eff = max((float) ($part->efficiency ?? 100), 1);
                $ratePerHour = $rate * ($eff / 100);
            }

            return $ratePerHour > 0 ? $plannedQty / $ratePerHour : 0.0;
        };

        $parts = $planParts->values()->all();
        $n = count($parts);
        $cursor = 0.0; // horas acumuladas desde el inicio del turno
        $i = 0;

        while ($i < $n) {
            // Reunir el grupo de partes que van en paralelo (misma clave de grupo).
            // El orden previo deja juntas las partes de la misma clave.
            $key = $groupKey($parts[$i]);
            $group = [$parts[$i]];
            $j = $i + 1;
            while ($j < $n && $groupKey($parts[$j]) === $key) {
                $group[] = $parts[$j];
                $j++;
            }

            // Todas las del grupo arrancan en $cursor; el reloj avanza con la más larga.
            $planStart = $cursor;
            $groupEnd = $cursor;

            foreach ($group as $part) {
                $planEnd = $planStart + $durationFor($part);
                $groupEnd = max($groupEnd, $planEnd);

                // Recortar la barra a "ahora"; nada de plan en el futuro
                $visibleEnd = min($planEnd, $cap);
                if ($visibleEnd <= $planStart) {
                    continue;
                }

                $this->planRanges[] = ['x' => [round($planStart, 3), round($visibleEnd, 3)], 'y' => $part->part_number];
                $this->planQuantities[] = (int) $part->planned_quantity;

                // Tooltip: ventana planeada COMPLETA (aunque la barra esté recortada)
                $ps = $baseStart->copy()->addSeconds((int) round($planStart * 3600));
                $pe = $baseStart->copy()->addSeconds((int) round($planEnd * 3600));
                $this->planTimeLabels[] = $ps->format('d-m H:i') . ' - ' . $pe->format('d-m H:i');
            }

            $cursor = $groupEnd;
            $i = $j;
        }
    }

    private function dispatchTimeline(): void
    {
        $this->dispatch('update-timeline', [
            'labels' => $this->labels,
            'ranges' => $this->ranges,
            'quantities' => $this->quantities,
            'timeLabels' => $this->timeLabels,
            'planRanges' => $this->planRanges,
            'planQuantities' => $this->planQuantities,
            'planTimeLabels' => $this->planTimeLabels,
            'durationHours' => $this->durationHours,
            'shiftStartIso' => $this->shiftStartIso,
            'isLive' => $this->isLive,
        ]);
    }

    public function render()
    {
        return view('livewire.shift-production-timeline');
    }
}
