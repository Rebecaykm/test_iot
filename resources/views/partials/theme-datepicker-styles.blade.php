{{-- Flatpickr (calendario) con tema propio a juego con la paleta slate/indigo del proyecto.
     Incluir dentro de @section('css'), DESPUÉS de @include('partials.theme-styles'), solo en
     vistas que tengan inputs de fecha. Requiere partials.theme-datepicker-scripts en @section('js')
     y que cada input use: type="text" class="filter-input flatpickr-date" --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
    .flatpickr-calendar {
        font-family: 'Inter', sans-serif;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        width: 280px !important;
    }
    /* Flatpickr fija el ancho de estos contenedores internos (307.875px) en su propio
       CSS; si no se ajustan al ancho custom de arriba, .flatpickr-innerContainer (que
       tiene overflow:hidden) recorta la grilla de días y el calendario se ve incompleto.
       Se usa 100% (no un px fijo) porque el calendario tiene 1.5px de borde a cada lado:
       forzarlos también a 280px exactos los hacía 3px más anchos que el área interior
       real (280 - 2×1.5), y ese excedente se acumulaba del lado derecho quitándole margen. */
    .flatpickr-innerContainer,
    .flatpickr-rContainer,
    .flatpickr-days,
    .dayContainer {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
    }
    .flatpickr-calendar.arrowTop::before,
    .flatpickr-calendar.arrowTop::after {
        display: none;
    }
    .flatpickr-months {
        padding: 0.6rem 0.5rem 0.2rem;
    }
    .flatpickr-current-month {
        font-size: 0.9rem;
        font-weight: 600;
        color: #334155;
        padding: 0;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months {
        font-weight: 600;
        color: #334155;
    }
    .flatpickr-current-month input.cur-year {
        font-weight: 600;
        color: #334155;
    }
    .flatpickr-prev-month,
    .flatpickr-next-month {
        border-radius: 6px;
        transition: background 0.12s;
    }
    .flatpickr-prev-month:hover,
    .flatpickr-next-month:hover {
        background: #eff6ff;
    }
    .flatpickr-prev-month svg,
    .flatpickr-next-month svg {
        fill: #64748b;
    }
    .flatpickr-prev-month:hover svg,
    .flatpickr-next-month:hover svg {
        fill: #1d4ed8;
    }
    .flatpickr-weekdays {
        margin-top: 0.3rem;
    }
    span.flatpickr-weekday {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #94a3b8;
    }
    .flatpickr-days {
        border: none;
        /* Sin padding horizontal: .dayContainer se fija internamente a un ancho
           igual al de .flatpickr-days (min-width = max-width = width en su propio
           CSS), así que cualquier padding lateral aquí le roba espacio y hace que
           la última columna (domingo) se recorte por el overflow:hidden del
           contenedor. El espaciado horizontal ya lo da justify-content:space-around
           en .dayContainer. */
        padding: 0.2rem 0 0.5rem;
    }
    .dayContainer {
        justify-content: space-around;
    }
    .flatpickr-day {
        border-radius: 7px;
        font-size: 0.82rem;
        color: #334155;
        max-width: 36px;
        height: 36px;
        line-height: 36px;
    }
    .flatpickr-day:hover,
    .flatpickr-day:focus {
        background: #eff6ff;
        border-color: #eff6ff;
        color: #1d4ed8;
    }
    .flatpickr-day.today {
        border-color: #93c5fd;
    }
    .flatpickr-day.today:hover {
        border-color: #93c5fd;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover,
    .flatpickr-day.selected:focus {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }
    .flatpickr-day.flatpickr-disabled,
    .flatpickr-day.flatpickr-disabled:hover,
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay {
        color: #cbd5e1;
    }
    .flatpickr-day.flatpickr-disabled {
        cursor: not-allowed;
    }
    .flatpickr-months .flatpickr-month {
        color: #334155;
        fill: #334155;
    }
</style>
