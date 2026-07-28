<x-guest-layout>
@php
$items    = $data['data'] ?? [];
$hCount   = count($hourlyRange);
$segments = max(1, $hCount - 1);

// Build full datetime for shift boundaries from the first item or the $date param
$firstItem  = $items[0] ?? null;
$dayPart    = $firstItem ? \Carbon\Carbon::parse($firstItem['start_date'])->format('Y-m-d') : ($date ?? now()->format('Y-m-d'));
$shiftStart = \Carbon\Carbon::parse($dayPart . ' ' . ($hourlyRange[0] ?? '08:00') . ':00');
$shiftEnd   = \Carbon\Carbon::parse($dayPart . ' ' . (end($hourlyRange) ?: '20:00') . ':00');
if ($shiftEnd->lte($shiftStart)) {
    $shiftEnd->addDay(); // Night shift crosses midnight
}
$totalMins = max(1, $shiftStart->diffInMinutes($shiftEnd));

// Summary counters
$overflowCount = collect($items)->where('status', 'overflow')->count();
$normalCount   = collect($items)->where('status', 'normal')->count();
$warningCount  = collect($items)->where('status', 'warning')->count();

// Global totals for mini indicator
$totalPlanned   = collect($items)->sum('plannedPieces');
$totalCompleted = (float) collect($items)->sum('completedPieces');
$globalPct      = $totalPlanned > 0 ? round(($totalCompleted / $totalPlanned) * 100, 1) : 0;
$globalOverflow = max(0, round($globalPct - 100, 1));
$globalStatus   = $globalPct > 100 ? 'overflow' : ($globalPct >= 95 ? 'normal' : 'warning');
$globalFill     = min(100, $globalPct);
$globalBarColor = match($globalStatus) {
    'overflow' => '#ef4444',
    'normal'   => '#22c55e',
    default    => '#f59e0b',
};
$globalTextColor = match($globalStatus) {
    'overflow' => 'text-red-600',
    'normal'   => 'text-green-600',
    default    => 'text-amber-500',
};

// Fixed column widths (px)
$colProduct = 140;
$colLine    = 110;
$colSnp     = 55;
$colPlan    = 52;
$colReal    = 52;
$colPct     = 66;
$colBands   = 140;
$fixedWidth = $colProduct + $colLine + $colSnp + $colPlan + $colReal + $colPct + $colBands;
@endphp

<style>
    .tl-wrap  { overflow-x: auto; overflow-y: visible; }
    .tl-inner { min-width: {{ $fixedWidth + 900 }}px; }

    .tl-header-row,
    .tl-task-row   { display: flex; align-items: stretch; border-bottom: 1px solid #f1f5f9; }
    .tl-header-row { position: sticky; top: 0; z-index: 20; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .tl-task-row:hover { background: #f8fafc; }

    .col-product { width: {{ $colProduct }}px; min-width: {{ $colProduct }}px; }
    .col-line    { width: {{ $colLine }}px;    min-width: {{ $colLine }}px; }
    .col-snp     { width: {{ $colSnp }}px;     min-width: {{ $colSnp }}px; }
    .col-plan    { width: {{ $colPlan }}px;    min-width: {{ $colPlan }}px; }
    .col-real    { width: {{ $colReal }}px;    min-width: {{ $colReal }}px; }
    .col-pct     { width: {{ $colPct }}px;     min-width: {{ $colPct }}px; }
    .col-tl      { flex: 1; position: relative; }

    .hour-ruler  { position: relative; height: 32px; }
    .hour-tick   {
        position: absolute; top: 0; height: 100%;
        display: flex; flex-direction: column; align-items: center;
        transform: translateX(-50%);
    }
    .hour-tick span     { font-size: 10px; color: #94a3b8; padding-top: 4px; white-space: nowrap; }
    .hour-tick-line     { width: 1px; flex: 1; background: #e2e8f0; margin-top: 2px; }

    .tl-grid {
        position: absolute; inset: 0; pointer-events: none;
        background-image: repeating-linear-gradient(
            to right,
            transparent 0px,
            transparent calc({{ 100 / $segments }}% - 1px),
            #f1f5f9 calc({{ 100 / $segments }}% - 1px),
            #f1f5f9 calc({{ 100 / $segments }}%)
        );
    }

    .task-bar {
        position: absolute;
        top: 4px; bottom: 4px;
        border-radius: 4px;
        display: flex; align-items: center;
        overflow: hidden;
        min-width: 4px;
        font-size: 10px; font-weight: 600; color: #fff;
        white-space: nowrap;
    }
    .task-bar.normal   { background: #22c55e; }
    .task-bar.overflow { background: #ef4444; }
    .task-bar.warning  { background: #f59e0b; }

    .overflow-badge {
        position: absolute;
        top: 50%; transform: translateY(-50%);
        background: #dc2626; color: #fff;
        font-size: 9px; font-weight: 700;
        padding: 1px 4px; border-radius: 3px;
        white-space: nowrap; pointer-events: none; z-index: 5;
    }

    .cell        { padding: 5px 6px; font-size: 11px; display: flex; align-items: center; }
    .cell-right  { justify-content: flex-end; }
    .cell-center { justify-content: center; }
    .th          { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; padding: 6px 6px; display: flex; align-items: center; }
    .th-right    { justify-content: flex-end; }
    .th-center   { justify-content: center; }

    .badge          { display: inline-flex; align-items: center; padding: 1px 6px; border-radius: 9999px; font-size: 10px; font-weight: 700; }
    .badge-overflow { background: #fee2e2; color: #b91c1c; }
    .badge-normal   { background: #dcfce7; color: #15803d; }
    .badge-warning  { background: #fef3c7; color: #b45309; }

    .col-bands { width: {{ $colBands }}px; min-width: {{ $colBands }}px; padding: 4px 6px; display: flex; flex-direction: column; justify-content: center; gap: 3px; }

    .band-row  { display: flex; align-items: center; gap: 4px; }
    .band-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #94a3b8; width: 26px; flex-shrink: 0; }
    .band-track { flex: 1; height: 8px; background: #f1f5f9; border-radius: 99px; overflow: visible; position: relative; }
    .band-fill  { height: 100%; border-radius: 99px; position: relative; }
    .band-fill.plan-fill  { background: #818cf8; }
    .band-fill.real-fill.normal   { background: #22c55e; }
    .band-fill.real-fill.overflow { background: #ef4444; }
    .band-fill.real-fill.warning  { background: #f59e0b; }
    .band-value { font-size: 9px; font-weight: 700; color: #475569; width: 28px; text-align: right; flex-shrink: 0; white-space: nowrap; }
</style>

<div class="min-h-screen bg-slate-100 text-[11px] antialiased leading-tight"
     x-data="productionReport()" x-init="init()">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 shadow-sm px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-4">

            <div>
                <h1 class="text-xl font-bold text-slate-800">Gráfica de Producción por Turno</h1>
                <p class="text-slate-500 text-xs mt-0.5">
                    Turno:
                    <span class="font-semibold text-indigo-600">
                        {{ $shift ? $shift->label() . ' (' . $shift->value . ')' : 'Sin turno activo' }}
                    </span>
                    &mdash; {{ $hourlyRange[0] ?? '?' }} - {{ end($hourlyRange) ?: '?' }}
                </p>
            </div>

            {{-- Global mini indicator --}}
            <div class="flex flex-col gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 min-w-[220px]">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Turno global</span>
                    <span class="text-sm font-extrabold {{ $globalTextColor }}">
                        {{ $globalPct }}%
                        @if($globalOverflow > 0)
                            <span class="text-[10px] font-bold text-red-500">(+{{ $globalOverflow }}%)</span>
                        @endif
                    </span>
                </div>

                {{-- Plan ribbon --}}
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold uppercase text-slate-400 w-7 shrink-0">Plan</span>
                    <div class="flex-1 h-2.5 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-indigo-400" style="width:100%"></div>
                    </div>
                    <span class="text-[9px] font-bold text-slate-500 w-20 text-right shrink-0 tabular-nums">
                        {{ number_format($totalPlanned) }} labels
                    </span>
                </div>

                {{-- Real ribbon --}}
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold uppercase text-slate-400 w-7 shrink-0">Real</span>
                    <div class="flex-1 h-2.5 bg-slate-200 rounded-full overflow-hidden relative">
                        <div class="h-full rounded-full transition-all"
                             style="width:{{ $globalFill }}%; background:{{ $globalBarColor }}"></div>
                    </div>
                    <span class="text-[9px] font-bold w-20 text-right shrink-0 tabular-nums {{ $globalTextColor }}">
                        {{ number_format($totalCompleted) }} labels
                    </span>
                </div>
            </div>

            {{-- Date navigation --}}
            <form id="filterForm" method="GET"
                  action="{{ route('production-tracking.hourly-shift-report') }}"
                  class="flex items-center gap-2">

                <button type="button"
                        class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition"
                        @click="subtractDay()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>

                <input type="date" name="date"
                       x-model="searchDate"
                       @change="$el.closest('form').submit()"
                       class="rounded-lg border border-slate-300 bg-white py-1.5 px-3 text-sm
                              text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none
                              focus:ring-1 focus:ring-indigo-500"/>

                <button type="button"
                        class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition"
                        @click="addDay()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>

                <span class="font-mono text-slate-400 text-xs ml-1" x-text="currentTime"></span>
            </form>
        </div>

        {{-- Summary pills --}}
        <div class="flex flex-wrap gap-3 mt-3">
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 bg-slate-100 rounded-full px-3 py-1">
                <span class="w-2 h-2 rounded-full bg-slate-400 inline-block"></span>
                Total: <strong>{{ count($items) }}</strong>
            </span>
            {{-- <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-full px-3 py-1">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                Normal (&ge;95%): <strong>{{ $normalCount }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 bg-amber-50 rounded-full px-3 py-1">
                <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
                Advertencia (&lt;95%): <strong>{{ $warningCount }}</strong>
            </span> --}}
            {{-- <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-full px-3 py-1">
                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                Desbordamiento (&gt;100%): <strong>{{ $overflowCount }}</strong>
            </span> --}}
        </div>

        {{-- Filters (non-functional) --}}
        <div class="flex flex-wrap items-center gap-3 mt-3 pt-3 border-t border-slate-100">
            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Filtros:</span>

            <div class="flex flex-col gap-0.5">
                <label class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Customer</label>
                <select disabled
                        class="rounded-md border border-slate-200 bg-slate-50 py-1 pl-2 pr-6 text-xs
                               text-slate-400 cursor-not-allowed opacity-60 focus:outline-none">
                    <option>Todos</option>
                </select>
            </div>

            <div class="flex flex-col gap-0.5">
                <label class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Line</label>
                <select disabled
                        class="rounded-md border border-slate-200 bg-slate-50 py-1 pl-2 pr-6 text-xs
                               text-slate-400 cursor-not-allowed opacity-60 focus:outline-none">
                    <option>Todas</option>
                </select>
            </div>

            <div class="flex flex-col gap-0.5">
                <label class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Class</label>
                <select disabled
                        class="rounded-md border border-slate-200 bg-slate-50 py-1 pl-2 pr-6 text-xs
                               text-slate-400 cursor-not-allowed opacity-60 focus:outline-none">
                    <option>Todas</option>
                </select>
            </div>

            <div class="flex flex-col gap-0.5">
                <label class="text-[9px] uppercase tracking-wide text-slate-400 font-semibold">Parent</label>
                <select disabled
                        class="rounded-md border border-slate-200 bg-slate-50 py-1 pl-2 pr-6 text-xs
                               text-slate-400 cursor-not-allowed opacity-60 focus:outline-none">
                    <option>Todos</option>
                </select>
            </div>

            <span class="text-[9px] text-slate-300 italic ml-1">(próximamente)</span>
        </div>
    </div>

    {{-- TIMELINE --}}
    <div class="tl-wrap px-3 py-4">
        <div class="tl-inner bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200">

            {{-- Header --}}
            <div class="tl-header-row border-b-2 border-slate-200">
                <div class="th col-product">Orden Producción</div>
                <div class="th col-product">Producto</div>
                <div class="th col-line">Línea</div>
                <div class="th col-snp th-center">SNP</div>
                <div class="th col-plan th-right">L. Planned</div>
                <div class="th col-real th-right">L. Completed</div>
                <div class="th col-pct th-right">%</div>
                <div class="col-tl">
                    <div class="hour-ruler">
                        @foreach($hourlyRange as $i => $hour)
                            @php $pct = ($hCount > 1) ? ($i / ($hCount - 1)) * 100 : 0; @endphp
                            <div class="hour-tick" style="left: {{ $pct }}%">
                                <span>{{ $hour }}</span>
                                <div class="hour-tick-line"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Task rows --}}
            @forelse($items as $task)
                @php
                    $start     = \Carbon\Carbon::parse($task['start_date']);
                    $end       = \Carbon\Carbon::parse($task['end_date']);
                    $leftMins  = max(0, $shiftStart->diffInMinutes($start, false));
                    $widthMins = max(0, $start->diffInMinutes($end, false));
                    $leftPct   = round(($leftMins / $totalMins) * 100, 4);
                    $widthPct  = round(max(0.25, ($widthMins / $totalMins) * 100), 4);
                    $rightPct  = $leftPct + $widthPct;

                    $status    = $task['status'] ?? 'warning';
                    $pct       = $task['percentage'] ?? 0;
                    $overflow  = $task['overflowPercentage'] ?? 0;

                    $badgeClass = match($status) {
                        'overflow' => 'badge-overflow',
                        'normal'   => 'badge-normal',
                        default    => 'badge-warning',
                    };
                @endphp

                <div class="tl-task-row">
                    <div class="cell col-product text-center font-mono font-semibold truncate {{ $task['productionOrder'] == '99' ? 'text-red-600' : 'text-slate-800' }}"
                         title="{{ $task['productionOrder'] }}">{{ sprintf('%02d', $task['productionOrder']) }}</div>
                    <div class="cell col-product font-mono font-semibold text-slate-800 truncate"
                         title="{{ $task['text'] }}">{{ $task['text'] }}</div>
                    <div class="cell col-line text-slate-500 truncate"
                         title="{{ $task['workcenterDescription'] }}">{{ $task['workcenterDescription'] }}</div>
                    <div class="cell col-snp cell-center text-slate-500">{{ $task['snp'] }}</div>
                    <div class="cell col-plan cell-right text-slate-500">{{ $task['plannedPieces'] }}</div>
                    <div class="cell col-real cell-right {{ $status === 'overflow' ? 'text-red-600 font-bold' : 'text-slate-700' }}">
                        {{ $task['percentage'] }}
                    </div>
                    <div class="cell col-pct cell-right">
                        <span class="badge {{ $badgeClass }}">{{ $pct }}%</span>
                    </div>


                    {{-- Timeline area --}}
                    <div class="col-tl" style="height: 28px; position: relative;">
                        <div class="tl-grid"></div>

                        <div class="task-bar {{ $status }}"
                             style="left: {{ $leftPct }}%; width: {{ $widthPct }}%;"
                             title="{{ $task['text'] }} | {{ $task['workcenterDescription'] }} | Inicio: {{ $start->format('Y-m-d H:i') }} | Fin: {{ $end->format('Y-m-d H:i') }} | Plan: {{ $task['plannedPieces'] }} | Real: {{ $task['completedPieces'] }} | {{ $pct }}%{{ $overflow > 0 ? ' (+' . $overflow . '% desbordamiento)' : '' }}">
                            @if($widthPct > 2.5)
                                <span class="px-1.5 truncate drop-shadow-sm">{{ $task['shopOrderNumber'] }}</span>
                            @endif
                        </div>

                        @if($status === 'overflow' && $overflow > 0)
                            <div class="overflow-badge" style="left: calc({{ $rightPct }}% + 3px);">
                                +{{ $overflow }}%
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center py-16 text-slate-400 text-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 opacity-40" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                    No hay datos de producci�n para esta fecha.
                </div>
            @endforelse

        </div>
    </div>
</div>

<script>
    function productionReport() {
        return {
            searchDate: '{{ $date ?? now()->format("Y-m-d") }}',
            currentTime: '',

            init() {
                this.tick();
                setInterval(() => this.tick(), 1000);
            },

            tick() {
                const now = new Date();
                this.currentTime = now.toLocaleTimeString('es-MX', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
                });
            },

            addDay() {
                const d = new Date(this.searchDate + 'T12:00:00');
                d.setDate(d.getDate() + 1);
                this.searchDate = d.toISOString().slice(0, 10);
                this.$nextTick(() => document.getElementById('filterForm').submit());
            },

            subtractDay() {
                const d = new Date(this.searchDate + 'T12:00:00');
                d.setDate(d.getDate() - 1);
                this.searchDate = d.toISOString().slice(0, 10);
                this.$nextTick(() => document.getElementById('filterForm').submit());
            },
        };
    }
</script>
</x-guest-layout>
