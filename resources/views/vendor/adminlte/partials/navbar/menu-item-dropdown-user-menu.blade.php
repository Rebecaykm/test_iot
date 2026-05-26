@php
    $logout_url = View::getSection('logout_url') ?? config('adminlte.logout_url', 'logout');

    if (config('adminlte.use_route_url', false)) {
        $logout_url = $logout_url ? route($logout_url) : '';
    } else {
        $logout_url = $logout_url ? url($logout_url) : '';
    }

    $nameParts = collect(explode(' ', trim(Auth::user()->name)))
        ->filter()
        ->map(fn($part) => strtoupper(substr($part, 0, 1)));
    $initials = $nameParts->take(2)->implode('');
@endphp

<li class="nav-item dropdown user-menu">

    {{-- Toggler: círculo con iniciales --}}
    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-toggle="dropdown" title="{{ Auth::user()->name }}">
        <div class="user-initials-avatar">{{ $initials }}</div>
    </a>

    {{-- Dropdown --}}
    <ul class="dropdown-menu dropdown-menu-right user-initials-dropdown">

        {{-- Cabecera con nombre completo --}}
        <li class="dropdown-header user-initials-header">
            <div class="user-initials-avatar user-initials-avatar--lg mx-auto mb-1">{{ $initials }}</div>
            <div class="font-weight-bold">{{ Auth::user()->name }}</div>
            <small class="text-muted">{{ Auth::user()->email }}</small>
        </li>

        <li class="dropdown-divider"></li>

        {{-- Cerrar sesión --}}
        <li>
            <form action="{{ $logout_url }}" method="POST" class="px-3 py-2">
                @csrf
                @if(config('adminlte.logout_method'))
                    @method(config('adminlte.logout_method'))
                @endif
                <button type="submit" class="btn btn-outline-danger btn-block btn-sm">
                    <i class="fas fa-fw fa-sign-out-alt mr-1"></i>
                    Cerrar sesión
                </button>
            </form>
        </li>

    </ul>

</li>
