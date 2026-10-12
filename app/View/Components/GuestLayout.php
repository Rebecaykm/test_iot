<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param bool $floatingThemeToggle Botón flotante de tema (abajo a la derecha).
     *                                  Las páginas con <x-guest-header> lo apagan: lo traen en el header.
     */
    public function __construct(public bool $floatingThemeToggle = true)
    {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
