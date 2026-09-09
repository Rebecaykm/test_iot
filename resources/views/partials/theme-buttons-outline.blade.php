{{-- Botones en estilo outline — decisión explícita, incluido solo por las vistas que lo piden
     (actualmente: part-numbers, work-centers, material-validations, production-records, areas,
     lines, projects). Divergencia intencional respecto a theme-styles.blade.php: users y tag-types
     siguen usando el estilo soft/sólido sin cambios. Incluir DESPUÉS de
     @include('partials.theme-styles') para que estas reglas ganen por orden de cascada. --}}
<style>
    .btn-action-primary {
        background: transparent;
        color: #1d4ed8;
        border-color: #1d4ed8;
    }
    .btn-action-primary:hover {
        background: #eff6ff;
        color: #1e40af;
        text-decoration: none;
    }
    .btn-action-success {
        background: transparent;
        color: #15803d;
        border-color: #15803d;
    }
    .btn-action-success:hover {
        background: #f0fdf4;
        color: #166534;
        text-decoration: none;
    }
    .btn-action-danger {
        background: transparent;
        color: #b91c1c;
        border-color: #b91c1c;
    }
    .btn-action-danger:hover {
        background: #fef2f2;
        color: #991b1b;
        text-decoration: none;
    }
    .btn-action-solid {
        background: transparent;
        color: #1d4ed8;
        border-color: #1d4ed8;
    }
    .btn-action-solid:hover {
        background: #eff6ff;
        color: #1e40af;
        text-decoration: none;
    }
    .btn-action-warning {
        background: transparent;
        color: #b45309;
        border-color: #b45309;
    }
    .btn-action-warning:hover {
        background: #fff8ec;
        color: #92400e;
        text-decoration: none;
    }
</style>
