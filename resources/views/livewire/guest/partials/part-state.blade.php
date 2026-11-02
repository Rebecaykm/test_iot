{{-- Estado de la orden del turno: 'live' (En progreso) o 'stop' (Detenido); null no pinta nada --}}
@if($state === 'live')
    <span class="lp-state lp-state-live flex-shrink-0"><span class="lp-dot lp-dot-green"></span>EN PROCESO</span>
@elseif($state === 'stop')
    <span class="lp-state lp-state-stop flex-shrink-0"><span class="lp-dot-stop"></span>DETENIDO</span>
@endif
