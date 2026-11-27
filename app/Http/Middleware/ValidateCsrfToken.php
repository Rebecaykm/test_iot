<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class ValidateCsrfToken extends Middleware
{
    /**
     * Dentro de una miniatura de otro sitio (el Log Board) el navegador no envía cookies, así que no hay sesión
     * contra la cual comparar el token: Livewire recibía 419 y la página se recargaba sin fin.
     *
     * Esas peticiones se dejan pasar solo si cumplen todo: son de Livewire, no traen ninguna cookie (no pueden
     * actuar en nombre de nadie) y su origen es este mismo sistema (un sitio ajeno no puede falsificarlo).
     */
    protected function tokensMatch($request)
    {
        return $this->isPreviewRequest($request) || parent::tokensMatch($request);
    }

    private function isPreviewRequest($request): bool
    {
        return $request->routeIs('*livewire.update')
            && $request->cookies->count() === 0
            && $request->headers->get('Origin') === $request->getSchemeAndHttpHost();
    }
}
