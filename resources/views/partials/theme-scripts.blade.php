{{-- Scripts compartidos del tema. Incluir dentro de @section('js').
     Opcional: pasar 'deleteMessage' para el texto de confirmación de los .delete-form --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Cerrar alertas automáticamente después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(el => $(el).alert('close'));
        }, 5000);

        // Confirmación antes de enviar formularios de eliminación
        const deleteMessage = @json($deleteMessage ?? '¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.');

        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: '¡No podrás revertir esta acción!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#1d4ed8',
                        cancelButtonColor: '#b91c1c',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
                    });
                } else if (confirm(deleteMessage)) {
                    this.submit();
                }
            });
        });
    });
</script>
