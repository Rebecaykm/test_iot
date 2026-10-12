{{--
    Reloj de las vistas de piso: día y fecha a la izquierda, hora grande a la derecha.
    Alpine en lugar de getElementById: se puede usar varias veces en una página sin chocar.
    El servidor pinta el valor inicial para que no aparezca vacío antes de que cargue el JS.
--}}
@php
    $now = now();
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
@endphp
<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-3.5 pr-4 py-1.5 shadow-sm']) }}
    x-data="{
        now: new Date(),
        dias: @js($dias),
        meses: @js($meses),
        pad: n => String(n).padStart(2, '0'),
        init() { setInterval(() => this.now = new Date(), 1000); },
    }">
    <div class="text-right leading-tight">
        <div class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400" x-text="dias[now.getDay()]">{{ $dias[$now->dayOfWeek] }}</div>
        <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200 whitespace-nowrap" x-text="`${pad(now.getDate())} ${meses[now.getMonth()]} ${now.getFullYear()}`">{{ $now->format('d') }} {{ $meses[$now->month - 1] }} {{ $now->year }}</div>
    </div>

    <div class="h-8 w-px bg-zinc-200 dark:bg-zinc-700" aria-hidden="true"></div>

    <time class="font-bold tabular-nums tracking-tight text-zinc-900 dark:text-zinc-50 whitespace-nowrap" :datetime="now.toISOString()">
        <span class="text-2xl" x-text="`${pad(now.getHours())}:${pad(now.getMinutes())}`">{{ $now->format('H:i') }}</span><span class="text-base text-zinc-500 dark:text-zinc-400" x-text="`:${pad(now.getSeconds())}`">:{{ $now->format('s') }}</span>
    </time>
</div>
