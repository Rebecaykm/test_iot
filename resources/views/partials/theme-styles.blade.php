{{-- Estilos compartidos del tema (Inter). Incluir dentro de @section('css') --}}
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body, .card, .btn, .form-control, .table, .content-header h1 {
        font-family: 'Inter', sans-serif !important;
    }

    /* ── Títulos de sección ── */
    .section-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #334155;
    }

    /* ── Botones de acción ── */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.42rem 0.9rem;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 7px;
        border: 1.5px solid transparent;
        text-decoration: none;
        transition: all 0.15s ease;
        white-space: nowrap;
        cursor: pointer;
    }
    .btn-action-sm {
        padding: 0.3rem 0.7rem;
        font-size: 0.75rem;
    }
    .btn-action-primary {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #93c5fd;
    }
    .btn-action-primary:hover {
        background: #dbeafe;
        color: #1e40af;
        text-decoration: none;
    }
    .btn-action-secondary {
        background: transparent;
        color: #64748b;
        border-color: #e2e8f0;
    }
    .btn-action-secondary:hover {
        background: #f1f5f9;
        color: #475569;
        text-decoration: none;
    }
    .btn-action-success {
        background: #f0fdf4;
        color: #15803d;
        border-color: #86efac;
    }
    .btn-action-success:hover {
        background: #dcfce7;
        color: #166534;
        text-decoration: none;
    }
    .btn-action-warning {
        background: #fff8ec;
        color: #b45309;
        border-color: #fcd34d;
    }
    .btn-action-warning:hover {
        background: #fef3c7;
        color: #92400e;
        text-decoration: none;
    }
    .btn-action-danger {
        background: #fff1f2;
        color: #b91c1c;
        border-color: #fca5a5;
    }
    .btn-action-danger:hover {
        background: #fee2e2;
        color: #991b1b;
        text-decoration: none;
    }
    .btn-action-solid {
        background: #1d4ed8;
        color: #fff;
        border-color: #1d4ed8;
    }
    .btn-action-solid:hover {
        background: #1e40af;
        border-color: #1e40af;
        color: #fff;
    }
    .btn-action:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    /* ── Filtros ── */
    .filter-group {
        display: inline-flex;
        align-items: center;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
        padding: 0 0.6rem;
        height: 34px;
        transition: border-color 0.15s;
    }
    .filter-group:focus-within {
        border-color: #93c5fd;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
    }
    .filter-icon {
        color: #94a3b8;
        font-size: 0.75rem;
        margin-right: 0.45rem;
    }
    .filter-input {
        border: none;
        background: transparent;
        font-size: 0.82rem;
        color: #334155;
        outline: none;
        height: 100%;
        width: 100%;
        font-family: 'Inter', sans-serif;
    }
    .filter-input::placeholder {
        color: #94a3b8;
    }
    .btn-filter-submit {
        display: inline-flex;
        align-items: center;
        height: 34px;
        padding: 0 0.85rem;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 7px;
        background: #1d4ed8;
        color: #fff;
        border: none;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-filter-submit:hover {
        background: #1e40af;
    }
    .btn-filter-clear {
        display: inline-flex;
        align-items: center;
        height: 34px;
        padding: 0 0.75rem;
        font-size: 0.8rem;
        font-weight: 500;
        border-radius: 7px;
        background: transparent;
        color: #64748b;
        border: 1.5px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.15s;
    }
    .btn-filter-clear:hover {
        background: #f1f5f9;
        color: #475569;
        text-decoration: none;
    }

    /* ── Tabla ── */
    .table-head-row {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .th-cell {
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b !important;
        border: none !important;
        padding: 0.65rem 0.85rem !important;
        white-space: nowrap;
    }
    .td-row {
        border-bottom: 1px solid #f1f5f9 !important;
        transition: background 0.1s ease;
    }
    .td-row:hover {
        background-color: #f8fafc !important;
    }
    .td-cell {
        padding: 0.5rem 0.85rem !important;
        vertical-align: middle !important;
        border-top: none !important;
    }
    .row-current {
        background-color: #fefce8 !important;
    }
    .row-current:hover {
        background-color: #fef9c3 !important;
    }

    /* ── Badges ── */
    .badge-soft {
        display: inline-flex;
        align-items: center;
        padding: 0.28em 0.65em;
        border-radius: 5px;
        font-size: 0.73rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-soft.badge-primary   { background: #eff6ff; color: #1d4ed8; }
    .badge-soft.badge-success   { background: #f0fdf4; color: #15803d; }
    .badge-soft.badge-danger    { background: #fef2f2; color: #b91c1c; }
    .badge-soft.badge-warning   { background: #fefce8; color: #92400e; }
    .badge-soft.badge-secondary { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

    /* ── Campos de formulario ── */
    .field-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b;
        margin-bottom: 0.4rem;
    }
    .field-readonly {
        display: flex;
        align-items: center;
        min-height: 38px;
        padding: 0.4rem 0.75rem;
        font-size: 0.85rem;
        color: #334155;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
    }
    .field-input {
        display: block;
        width: 100%;
        height: 38px;
        padding: 0.4rem 0.75rem;
        font-size: 0.85rem;
        font-family: 'Inter', sans-serif;
        color: #334155;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    textarea.field-input {
        height: auto;
        min-height: 90px;
        resize: vertical;
    }
    .field-input:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
    }
    .field-input::placeholder {
        color: #94a3b8;
    }
    .field-input.is-invalid {
        border-color: #fca5a5;
    }
    .field-input.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(252, 165, 165, 0.25);
    }
    .field-error {
        font-size: 0.78rem;
        color: #b91c1c;
        margin-top: 0.3rem;
    }
    .field-hint {
        display: block;
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 0.3rem;
    }

    /* ── Contenedor de checkboxes (estaciones, etc.) ── */
    .check-container {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
        padding: 1rem;
        max-height: 300px;
        overflow-y: auto;
    }
    .check-container .form-check-label {
        font-size: 0.82rem;
        color: #334155;
        cursor: pointer;
    }

    /* ── Select2 (si está presente) ── */
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 7px !important;
        font-family: 'Inter', sans-serif !important;
        background: #fff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 35px !important;
        padding-left: 12px !important;
        font-size: 0.85rem !important;
        color: #334155 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 35px !important;
        right: 8px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #93c5fd !important;
        box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2) !important;
    }
    .select2-dropdown {
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 7px !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }
    .select2-results__option {
        font-family: 'Inter', sans-serif !important;
        font-size: 0.85rem !important;
        padding: 8px 12px !important;
    }
    .select2-results__option--highlighted {
        background-color: #1d4ed8 !important;
    }

    /* ── Paginación ── */
    .pagination { margin-bottom: 0; }
    .pagination .page-link {
        border-radius: 6px !important;
        margin: 0 2px;
        border-color: #e2e8f0;
        color: #475569;
        font-size: 0.8rem;
        padding: 0.3rem 0.6rem;
    }
    .pagination .page-item.active .page-link {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }
    .pagination .page-item.disabled .page-link { color: #cbd5e1; }

    .fw-500 { font-weight: 500; }
    .fw-600 { font-weight: 600; }

    @media (max-width: 767px) {
        .filter-group, .btn-filter-submit, .btn-filter-clear {
            width: 100%;
        }
        .card-header form > div {
            flex-direction: column;
        }
    }
</style>
