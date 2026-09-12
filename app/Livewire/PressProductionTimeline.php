<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\PartNumber;
use App\Models\PressTimelineNote;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Línea de tiempo de producción de prensas (estampado) agrupada por MDI (troquel).
 *
 * Cada fila del eje Y es un MDI. Todo se mide en GOLPES, no en piezas:
 *   golpes = piezas / divisor del troquel (suma de pieces_per_shot de los parts en juego).
 *
 *  - Barra PLAN (azul): golpes planeados del MDI secuenciados por production_order.
 *    production_rate guarda piezas/hora de cada part; se convierte a SPM del
 *    troquel con SPM = production_rate / (60 * pieces_per_shot), y de ahí
 *    golpes/hora = SPM * 60.
 *  - Barra REAL: golpes registrados en histories dentro de la ventana del turno.
 *    Color según el cumplimiento vs el plan total del MDI:
 *    >= 90% verde, entre 10% y 90% ámbar, < 10% rojo.
 */
class PressProductionTimeline extends Component
{
    /** Separación máxima (minutos) entre registros de histories para considerarlos la misma corrida */
    private const SEGMENT_GAP_MINUTES = 20;

    /** % de cumplimiento (real vs plan total del MDI) a partir del cual la barra es verde */
    private const GOOD_THRESHOLD = 90;

    /** % de cumplimiento a partir del cual la barra es ámbar; por debajo es roja */
    private const MID_THRESHOLD = 10;

    public string $chartId;
    public $workCenter;

    /** Etiquetas del eje Y: un MDI por fila, en orden de producción */
    public array $labels = [];

    /** Estado por fila ('good'|'bad'|'none') para colorear el texto y punto del MDI */
    public array $rowStatuses = [];

    /** Rango de cada barra de PLAN: [{x:[ini,fin], y:mdi}, ...] (horas desde inicio del turno) */
    public array $planRanges = [];

    /** Texto centrado en la barra de plan: golpes que debería llevar (o el total si ya venció) */
    public array $planProgress = [];

    /** Golpes totales planeados por barra de plan (tooltip) */
    public array $planTotals = [];

    /** Ventana planeada completa "dd-mm HH:mm - dd-mm HH:mm" por barra de plan (tooltip) */
    public array $planTimeLabels = [];

    /** Rango de cada segmento REAL: [{x:[ini,fin], y:mdi}, ...] */
    public array $ranges = [];

    /** Golpes del segmento real (texto centrado) */
    public array $quantities = [];

    /** Horario del segmento real (tooltip) */
    public array $timeLabels = [];

    /** Estado del segmento real ('good'|'bad'|'none') para el color de la barra */
    public array $barStatuses = [];

    /** Golpes totales del MDI por segmento real (tooltip) */
    public array $realTotals = [];

    /** Número de notas por fila, paralelo a $labels (para el badge del MDI) */
    public array $noteCounts = [];

    /** Notas del turno/fecha actual, agrupadas por MDI (label => array<PressTimelineNote>) */
    public array $notesByMdi = [];

    /** MDI cuyo panel de notas está abierto (null = ninguno) */
    public ?string $openNoteMdi = null;

    /** Texto de la nota que se está redactando */
    public string $noteDraft = '';

    /** Ancho total del eje X en horas */
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
        $this->chartId = 'press_timeline_' . Str::random(10);
        $this->workCenter = $workCenter;

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
        $this->openNoteMdi = null;
        $this->refreshTimeline();
    }

    public function updatedSelectedShiftId(): void
    {
        $this->openNoteMdi = null;
        $this->refreshTimeline();
    }

    /** Abre (o cambia a) el panel de notas de un MDI, para el turno/fecha actual */
    public function openNotesFor(string $mdi): void
    {
        $this->openNoteMdi = $mdi;
        $this->noteDraft = '';
    }

    public function closeNotes(): void
    {
        $this->openNoteMdi = null;
    }

    /** Guarda la nota redactada para el MDI con el panel abierto */
    public function saveNote(): void
    {
        if (!$this->openNoteMdi || !$this->selectedShiftId || !$this->selectedDate) {
            return;
        }

        $this->validate([
            'noteDraft' => 'required|string|max:2000',
        ]);

        PressTimelineNote::create([
            'work_center' => $this->workCenter,
            'mdi' => $this->openNoteMdi,
            'shift_id' => $this->selectedShiftId,
            'date' => $this->selectedDate,
            'body' => $this->noteDraft,
        ]);

        $this->noteDraft = '';
        $this->refreshTimeline();
    }

    /** Carga las notas del turno/fecha actual y el contador paralelo a $labels */
    private function loadNotes(): void
    {
        $shift = $this->selectedShiftId ? Shift::find($this->selectedShiftId) : null;
        $date = $this->selectedDate ? Carbon::parse($this->selectedDate) : null;

        // Se convierte a arreglo plano (label => array de modelos): Livewire no
        // sincroniza bien una Collection de Eloquent cuyos items son a su vez
        // Collections (lo que produce groupBy), como pasaría si se guardara tal cual.
        $this->notesByMdi = ($shift && $date)
            ? PressTimelineNote::forContext($this->workCenter, $shift->id, $date)
                ->map(fn ($group) => $group->all())
                ->all()
            : [];

        $this->noteCounts = array_map(
            fn ($label) => count($this->notesByMdi[$label] ?? []),
            $this->labels
        );
    }

    #[On('refresh-timeline')]
    public function refreshTimeline(): void
    {
        $now = Carbon::now();
        $this->lastUpdated = $now->format('H:i:s');

        $this->labels = [];
        $this->rowStatuses = [];
        $this->planRanges = [];
        $this->planProgress = [];
        $this->planTotals = [];
        $this->planTimeLabels = [];
        $this->ranges = [];
        $this->quantities = [];
        $this->timeLabels = [];
        $this->barStatuses = [];
        $this->realTotals = [];

        $shift = $this->selectedShiftId ? Shift::find($this->selectedShiftId) : null;
        $date = $this->selectedDate ? Carbon::parse($this->selectedDate) : null;
        $range = ($shift && $date) ? Shift::getShiftDateTimeRange($shift, $date) : null;

        // Selección inválida: mandar todo vacío
        if (!$range) {
            $this->isLive = false;
            $this->notesByMdi = [];
            $this->noteCounts = [];
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

        // Plan del turno por número de parte (se agrupa por MDI aquí)
        $planParts = ProductionRecord::getShiftPlannedSchedule(
            $this->workCenter,
            $shift->id,
            $date
        );

        // Real: registros de histories dentro de la ventana del turno
        $histories = History::getProducedTimeline($this->workCenter, $baseStart, $timelineEnd);

        $this->buildBars($planParts, $histories, $baseStart, $now);
        $this->loadNotes();

        $this->dispatchTimeline();
    }

    private function buildBars($planParts, $histories, Carbon $baseStart, Carbon $now): void
    {
        // Parts "en juego": planeados en el turno o con producción registrada
        $partIds = array_values(array_unique(array_merge(
            $planParts->pluck('part_number_id')->all(),
            $histories->pluck('part_number_id')->unique()->all()
        )));

        if (empty($partIds)) {
            return;
        }

        $parts = PartNumber::whereIn('id', $partIds)
            ->with(['customAttributes' => fn ($q) => $q->whereIn('key', ['mid', 'pieces_per_shot'])])
            ->get();

        // Troquel de cada part: su MDI; sin MDI => grupo propio etiquetado con el número de parte
        $dieKeyByPart = [];
        $dieLabelByPart = [];
        foreach ($parts as $p) {
            $attrs = $p->customAttributes->keyBy('key');
            $mid = trim((string) ($attrs['mid']->value ?? ''));
            $dieKeyByPart[$p->id] = $mid !== '' ? $mid : "part:{$p->id}";
            $dieLabelByPart[$p->id] = $mid !== '' ? $mid : (string) $p->number;
        }

        // Divisor piezas -> golpes por part (suma de pieces_per_shot del troquel en juego)
        $divisors = PartNumber::buildShotDivisors($parts);

        // pieces_per_shot PROPIO de cada part (no el divisor sumado del troquel),
        // para pasar su production_rate (piezas/hora) a SPM del troquel.
        $piecesPerShotByPart = [];
        foreach ($parts as $p) {
            $attrs = $p->customAttributes->keyBy('key');
            $piecesPerShotByPart[$p->id] = max(1, (int) ($attrs['pieces_per_shot']->value ?? 1));
        }

        // ----- Agregado del PLAN por MDI -----
        $dies = [];
        foreach ($planParts as $part) {
            $key = $dieKeyByPart[$part->part_number_id] ?? "part:{$part->part_number_id}";

            if (!isset($dies[$key])) {
                $dies[$key] = [
                    'label' => $dieLabelByPart[$part->part_number_id] ?? (string) $part->part_number,
                    'order' => PHP_INT_MAX,
                    'plannedPieces' => 0,
                    'divisor' => max(1, (int) ($divisors[$part->part_number_id] ?? 1)),
                    'rates' => [],
                ];
            }

            $dies[$key]['plannedPieces'] += (int) $part->planned_quantity;
            // SPM del MDI: promedio de los rates > 0 de sus parts (deberían ser iguales
            // por compartir troquel; el promedio absorbe capturas con diferencias).
            // production_rate viene en piezas/hora del part => SPM = rate / (60 * pps).
            if ((float) $part->production_rate > 0) {
                $pps = $piecesPerShotByPart[$part->part_number_id] ?? 1;
                $dies[$key]['rates'][] = (float) $part->production_rate / (60 * $pps);
            }
            if (!is_null($part->production_order)) {
                $dies[$key]['order'] = min($dies[$key]['order'], (int) $part->production_order);
            }
        }

        foreach ($dies as $key => $die) {
            $dies[$key]['rate'] = !empty($die['rates'])
                ? array_sum($die['rates']) / count($die['rates'])
                : 0.0;
        }

        // ----- Golpes REALES por MDI (histories) -----
        // Eventos por troquel convertidos a golpes: quantity / divisor del part
        $eventsByDie = [];
        foreach ($histories as $h) {
            $key = $dieKeyByPart[$h->part_number_id] ?? "part:{$h->part_number_id}";
            $eventsByDie[$key][] = [
                'time' => $h->created_at instanceof Carbon ? $h->created_at : Carbon::parse($h->created_at),
                'shots' => (float) $h->quantity / max(1, (int) ($divisors[$h->part_number_id] ?? 1)),
            ];
        }

        // MDIs con producción pero sin plan: filas extra al final
        foreach ($eventsByDie as $key => $events) {
            if (!isset($dies[$key])) {
                $firstPartId = $histories->first(fn ($h) => ($dieKeyByPart[$h->part_number_id] ?? '') === $key)?->part_number_id;
                $dies[$key] = [
                    'label' => $dieLabelByPart[$firstPartId] ?? (string) $key,
                    'order' => PHP_INT_MAX,
                    'plannedPieces' => 0,
                    'divisor' => 1,
                    'rate' => 0.0,
                ];
            }
        }

        // Momento en que cada MDI empezó a producirse (primer evento real)
        $firstEventTimeByDie = [];
        foreach ($eventsByDie as $key => $events) {
            $firstEventTimeByDie[$key] = min(array_map(fn ($e) => $e['time']->getTimestamp(), $events));
        }

        // Orden de las filas: primero los MDI que ya se produjeron, en el orden real
        // en que se empezaron a producir; al final los que aún no producen, en el
        // orden de plan de siempre (production_order asc, sin orden al final, luego MDI asc).
        $orderedKeys = array_keys($dies);
        usort($orderedKeys, function ($keyA, $keyB) use ($dies, $firstEventTimeByDie) {
            $aTime = $firstEventTimeByDie[$keyA] ?? null;
            $bTime = $firstEventTimeByDie[$keyB] ?? null;

            if ($aTime !== null && $bTime !== null) {
                return $aTime <=> $bTime;
            }
            if ($aTime !== null || $bTime !== null) {
                return $aTime !== null ? -1 : 1;
            }

            $a = $dies[$keyA];
            $b = $dies[$keyB];
            if ($a['order'] !== $b['order']) {
                return $a['order'] <=> $b['order'];
            }
            return strcmp($a['label'], $b['label']);
        });

        $sortedDies = [];
        foreach ($orderedKeys as $key) {
            $sortedDies[$key] = $dies[$key];
        }
        $dies = $sortedDies;

        // "Ahora" en horas desde el inicio del turno (sin acotar: si el turno ya pasó,
        // el plan completo cuenta como vencido y se muestra su total)
        $nowOffset = ($now->getTimestamp() - $baseStart->getTimestamp()) / 3600;
        $visibleCap = min($nowOffset, $this->durationHours);

        // Duración del plan de cada MDI: golpes / (SPM * 60). El SPM es el promedio de
        // los production_rate > 0 de sus parts, ya convertidos de piezas/hora a SPM.
        // Sin SPM (0) no se inventa duración ni cantidad: el MDI queda sin barra de plan.
        $plannedShotsByDie = [];
        $durationsByDie = [];
        foreach ($dies as $key => $die) {
            $plannedShots = $die['plannedPieces'] > 0
                ? (int) ceil($die['plannedPieces'] / $die['divisor'])
                : 0;
            $plannedShotsByDie[$key] = $plannedShots;

            $durationsByDie[$key] = ($plannedShots > 0 && $die['rate'] > 0)
                ? $plannedShots / ($die['rate'] * 60)
                : 0.0;
        }

        // ----- Secuenciar el plan y armar las filas -----
        $cursor = 0.0;
        foreach ($dies as $key => $die) {
            $plannedShots = $plannedShotsByDie[$key];
            $duration = (float) $durationsByDie[$key];

            $planStart = $cursor;
            $planEnd = $cursor + $duration;

            // Golpes que "debería llevar" el plan a esta hora (total si su ventana ya
            // venció). Sin duración (SPM en cero) no hay referencia: se queda en cero.
            $expectedShots = 0;
            if ($plannedShots > 0 && $duration > 0) {
                $elapsedFraction = max(0.0, min(1.0, ($nowOffset - $planStart) / $duration));
                $expectedShots = (int) round($plannedShots * $elapsedFraction);
            }

            // Golpes reales totales del MDI
            $events = $eventsByDie[$key] ?? [];
            $realShots = (int) round(array_sum(array_column($events, 'shots')));

            // Estado: cumplimiento del real vs el plan TOTAL del MDI => color de la
            // barra real y del texto del MDI. >= 90% verde, 10-90% ámbar, < 10% rojo.
            if ($plannedShots > 0) {
                $pct = ($realShots / $plannedShots) * 100;
                if ($pct >= self::GOOD_THRESHOLD) {
                    $status = 'good';
                } elseif ($pct >= self::MID_THRESHOLD) {
                    $status = 'mid';
                } else {
                    $status = 'bad';
                }
            } else {
                // Producción sin plan: sin referencia => neutro
                $status = 'none';
            }

            $this->labels[] = $die['label'];
            $this->rowStatuses[] = $status;

            // Barra de plan recortada a "ahora": no se pinta plan en el futuro
            if ($plannedShots > 0) {
                $visibleEnd = min($planEnd, $visibleCap);
                if ($visibleEnd > $planStart) {
                    $this->planRanges[] = ['x' => [round($planStart, 3), round($visibleEnd, 3)], 'y' => $die['label']];
                    $this->planProgress[] = $expectedShots;
                    $this->planTotals[] = $plannedShots;

                    $ps = $baseStart->copy()->addSeconds((int) round($planStart * 3600));
                    $pe = $baseStart->copy()->addSeconds((int) round($planEnd * 3600));
                    $this->planTimeLabels[] = $ps->format('d-m H:i') . ' - ' . $pe->format('d-m H:i');
                }

                $cursor = $planEnd;
            }

            // Segmentos reales: corridas continuas (cortar cuando hay hueco grande entre registros)
            $this->buildRealSegments($events, $die['label'], $baseStart, $status, $realShots);
        }
    }

    /**
     * Convierte los eventos de histories de un MDI en segmentos de barra. Un hueco
     * mayor a SEGMENT_GAP_MINUTES entre registros consecutivos corta el segmento,
     * así una corrida de 08:00-10:00 y otra de 14:00-16:00 se ven como dos barras.
     */
    private function buildRealSegments(array $events, string $label, Carbon $baseStart, string $status, int $realShots): void
    {
        if (empty($events)) {
            return;
        }

        usort($events, fn ($a, $b) => $a['time']->getTimestamp() <=> $b['time']->getTimestamp());

        $segments = [];
        $current = null;
        foreach ($events as $event) {
            $gapMinutes = $current
                ? ($event['time']->getTimestamp() - $current['end']->getTimestamp()) / 60
                : null;

            if ($current && $gapMinutes <= self::SEGMENT_GAP_MINUTES) {
                $current['end'] = $event['time'];
                $current['shots'] += $event['shots'];
            } else {
                if ($current) {
                    $segments[] = $current;
                }
                $current = ['start' => $event['time'], 'end' => $event['time'], 'shots' => $event['shots']];
            }
        }
        $segments[] = $current;

        foreach ($segments as $segment) {
            $startOffset = ($segment['start']->getTimestamp() - $baseStart->getTimestamp()) / 3600;
            $endOffset = ($segment['end']->getTimestamp() - $baseStart->getTimestamp()) / 3600;

            $startOffset = max(0, $startOffset);
            // Ancho mínimo para que un segmento de un solo registro sea visible
            $endOffset = max($endOffset, $startOffset + 0.05);
            $endOffset = min($endOffset, $this->durationHours);

            if ($endOffset <= $startOffset) {
                continue;
            }

            $this->ranges[] = ['x' => [round($startOffset, 3), round($endOffset, 3)], 'y' => $label];
            $this->quantities[] = (int) round($segment['shots']);
            $this->timeLabels[] = $segment['start']->format('d-m H:i') . ' - ' . $segment['end']->format('d-m H:i');
            $this->barStatuses[] = $status;
            $this->realTotals[] = $realShots;
        }
    }

    private function dispatchTimeline(): void
    {
        $this->dispatch('update-timeline', [
            'labels' => $this->labels,
            'rowStatuses' => $this->rowStatuses,
            'planRanges' => $this->planRanges,
            'planProgress' => $this->planProgress,
            'planTotals' => $this->planTotals,
            'planTimeLabels' => $this->planTimeLabels,
            'ranges' => $this->ranges,
            'quantities' => $this->quantities,
            'timeLabels' => $this->timeLabels,
            'barStatuses' => $this->barStatuses,
            'realTotals' => $this->realTotals,
            'noteCounts' => $this->noteCounts,
            'durationHours' => $this->durationHours,
            'shiftStartIso' => $this->shiftStartIso,
            'isLive' => $this->isLive,
        ]);
    }

    public function render()
    {
        return view('livewire.press-production-timeline');
    }
}
