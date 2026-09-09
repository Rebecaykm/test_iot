{{-- Inicializa Flatpickr en todo input.flatpickr-date de la página. Incluir dentro de
     @section('js'), junto con partials.theme-datepicker-styles en @section('css').
     Cada input debe usar: type="text" class="filter-input flatpickr-date"
     y opcionalmente data-auto-submit="1" para enviar el formulario al cambiar la fecha. --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input.flatpickr-date').forEach((el) => {
            flatpickr(el, {
                locale: 'es',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'j M Y',
                altInputClass: 'filter-input',
                maxDate: el.getAttribute('max') || undefined,
                minDate: el.getAttribute('min') || undefined,
                onChange: function () {
                    if (el.dataset.autoSubmit && el.form) {
                        el.form.submit();
                    }
                },
            });
        });
    });
</script>
