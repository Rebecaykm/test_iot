import './bootstrap';

import Chart from 'chart.js/auto';
window.Chart = Chart;

// Recuperación automática ante 419 (token CSRF expirado).
// Las pantallas tipo display hacen polling con Livewire; cuando alguien inicia o
// cierra sesión en otra pestaña el token CSRF cambia y esas peticiones reciben 419.
// En vez de mostrar el modal "This page has expired", recargamos para obtener un
// token nuevo sin intervención del operador.
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();          // evita el modal "page expired"
                window.location.reload();  // recarga con token CSRF fresco
            }
        });
    });
});
