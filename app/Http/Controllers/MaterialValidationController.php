<?php

namespace App\Http\Controllers;

use App\Models\MaterialValidation;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaterialValidationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $date = $request->input('date');

        $materialValidations = MaterialValidation::with(['workCenter', 'user'])
            ->when($search, function ($query, $search) {
                return $query->where('container_code', 'like', "%{$search}%")
                    ->orWhere('visual_aid_code', 'like', "%{$search}%")
                    ->orWhere('final_label_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('nickname', 'like', "%{$search}%");
                    });
            })
            ->when($date, function ($query, $date) {
                return $query->whereDate('created_at', $date);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends([
                'search' => $search,
                'date' => $date
            ]);

        return view('material-validations.index', compact('materialValidations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function statistics(Request $request)
    {
        try {
            // Validar y obtener fecha
            $date = $request->input('date')
                ? Carbon::parse($request->input('date'))
                : Carbon::now();

            $dateStr = $date->toDateString();

            // Obtener todos los turnos ordenados por hora de inicio
            $shifts = $this->getOrderedShifts();

            // Obtener información de turnos para la fecha seleccionada
            $shiftInfos = $this->getShiftInfos($shifts, $date);

            // Obtener todos los escaneos del día seleccionado con eager loading
            $scansToday = MaterialValidation::with(['user:id,name,nickname,profile_photo_path'])
                ->whereDate('created_at', $dateStr)
                ->select('id', 'user_id', 'validation_status', 'created_at')
                ->get();

            // Inicializar estadísticas por defecto
            $emptyStats = [
                'total' => 0,
                'ok' => 0,
                'ng' => 0,
                'ok_percentage' => 0,
                'ng_percentage' => 0
            ];

            // Calcular estadísticas o usar valores por defecto
            if ($scansToday->isEmpty()) {
                $dailyStats = $emptyStats;
                $shiftStats = $this->getEmptyShiftStats($shifts, $emptyStats);
                $userStats = collect();
                $hourlyStats = $this->getEmptyHourlyStats();
            } else {
                $dailyStats = $this->getDailyStats($scansToday);
                $shiftStats = $this->getShiftStats($shiftInfos);
                $userStats = $this->getUserStats($scansToday);
                $hourlyStats = $this->getHourlyStats($scansToday);
            }

            return view('material-validations.statistics', compact(
                'dailyStats',
                'shiftStats',
                'userStats',
                'hourlyStats',
                'shiftInfos',
                'shifts'
            ));
        } catch (\Exception $e) {
            // Log del error
            Log::error('Error en statistics: ' . $e->getMessage());

            // Redirigir con mensaje de error
            return redirect()->back()->with('error', 'Error al cargar las estadísticas. Inténtalo nuevamente.');
        }
    }

    /**
     * Obtener todos los turnos ordenados por hora de inicio
     */
    private function getOrderedShifts()
    {
        return Shift::orderBy('start_time', 'asc')->get();
    }

    /**
     * Obtener información de todos los turnos para la fecha dada
     */
    private function getShiftInfos($shifts, $date)
    {
        $shiftInfos = [];

        foreach ($shifts as $shift) {
            $shiftDate = $this->calculateShiftDate($shift, $date);
            $timeRange = $this->getShiftDateTimeRange($shift, $shiftDate);

            $shiftInfos[] = (object) [
                'shift' => $shift,
                'date' => $shiftDate,
                'timeRange' => $timeRange
            ];
        }

        return $shiftInfos;
    }

    /**
     * Calcular la fecha correcta para un turno específico
     * CORREGIDO: Manejo adecuado del turno nocturno
     */
    private function calculateShiftDate($shift, $referenceDate)
    {
        $date = Carbon::parse($referenceDate);

        if ($shift->abbreviation === 'D') {
            // Turno diurno: usar la fecha de referencia
            // Ejemplo: Si consulto el 4 de julio, muestro el turno diurno del 4 de julio (08:00-20:00)
            return $date->copy();
        }

        if ($shift->abbreviation === 'N') {
            // Turno nocturno: mostrar el turno que TERMINÓ en la fecha de referencia
            // Ejemplo: Si consulto el 4 de julio, muestro el turno nocturno que terminó el 4 de julio
            // (es decir, el que comenzó el 3 de julio a las 20:00 y terminó el 4 de julio a las 08:00)
            return $date->copy()->subDay();
        }

        // Para otros turnos, verificar si cruzan la medianoche
        if ($this->isNightShift($shift)) {
            return $date->copy()->subDay();
        }

        return $date->copy();
    }

    /**
     * Verificar si un turno es nocturno (cruza la medianoche)
     */
    private function isNightShift($shift)
    {
        $startTime = Carbon::createFromTimeString($shift->start_time);
        $endTime = Carbon::createFromTimeString($shift->end_time);

        // Si la hora de fin es menor que la de inicio, cruza la medianoche
        return $endTime->lessThan($startTime);
    }

    /**
     * Obtener rango de fecha y hora del turno
     */
    private function getShiftDateTimeRange($shift, $referenceDate)
    {
        if (!$shift) {
            return null;
        }

        $date = Carbon::parse($referenceDate);

        if ($shift->abbreviation === 'D') {
            // Turno diurno: 08:00 - 20:00 del mismo día
            $startDateTime = $date->copy()->setTimeFromTimeString($shift->start_time);
            $endDateTime = $date->copy()->setTimeFromTimeString($shift->end_time);
        } else {
            // Turno nocturno: 20:00 de un día - 08:00 del día siguiente
            $startDateTime = $date->copy()->setTimeFromTimeString($shift->start_time);
            $endDateTime = $date->copy()->addDay()->setTimeFromTimeString($shift->end_time);
        }

        return (object)[
            'shift' => $shift->abbreviation,
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime
        ];
    }

    /**
     * Obtener estadísticas de turnos ordenadas
     */
    private function getShiftStats($shiftInfos)
    {
        $stats = [];

        foreach ($shiftInfos as $shiftInfo) {
            $shiftScans = $this->getScansForShift($shiftInfo);
            $stats[$shiftInfo->shift->abbreviation] = $this->getDailyStats($shiftScans);
        }

        return $stats;
    }

    /**
     * Obtener estadísticas vacías para todos los turnos
     */
    private function getEmptyShiftStats($shifts, $emptyStats)
    {
        $stats = [];

        foreach ($shifts as $shift) {
            $stats[$shift->abbreviation] = $emptyStats;
        }

        return $stats;
    }

    private function getUserStats($scans)
    {
        return $scans->groupBy('user_id')
            ->map(function ($userScans) {
                $user = $userScans->first()->user;

                // Validar que el usuario existe
                if (!$user) {
                    return null;
                }

                $ok = $userScans->where('validation_status', 'OK')->count();
                $ng = $userScans->where('validation_status', 'NG')->count();
                $total = $userScans->count();

                return [
                    'user' => $user,
                    'ok' => $ok,
                    'ng' => $ng,
                    'total' => $total,
                    'ok_percentage' => $total > 0 ? round(($ok / $total) * 100, 2) : 0,
                    'ng_percentage' => $total > 0 ? round(($ng / $total) * 100, 2) : 0,
                ];
            })
            ->filter() // Remover valores null
            ->sortByDesc('total')
            ->values();
    }

    private function getDailyStats($scans)
    {
        $total = $scans->count();
        $ok = $scans->where('validation_status', 'OK')->count();
        $ng = $scans->where('validation_status', 'NG')->count();

        return [
            'total' => $total,
            'ok' => $ok,
            'ng' => $ng,
            'ok_percentage' => $total > 0 ? round(($ok / $total) * 100, 2) : 0,
            'ng_percentage' => $total > 0 ? round(($ng / $total) * 100, 2) : 0,
        ];
    }

    private function getScansForShift($shiftInfo)
    {
        if (!$shiftInfo || !$shiftInfo->shift || !$shiftInfo->timeRange) {
            return collect();
        }

        return MaterialValidation::whereBetween('created_at', [
            $shiftInfo->timeRange->startDateTime,
            $shiftInfo->timeRange->endDateTime
        ])
            ->select('id', 'validation_status', 'created_at')
            ->get();
    }

    private function getHourlyStats($scans)
    {
        // Inicializar array con 24 horas
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyData[$hour] = [
                'hour' => $hour,
                'ok' => 0,
                'ng' => 0,
                'total' => 0,
            ];
        }

        // Agrupar escaneos por hora
        $scansByHour = $scans->groupBy(function ($scan) {
            return $scan->created_at->hour;
        });

        // Llenar datos reales
        foreach ($scansByHour as $hour => $hourScans) {
            if ($hour >= 0 && $hour <= 23) {
                $ok = $hourScans->where('validation_status', 'OK')->count();
                $ng = $hourScans->where('validation_status', 'NG')->count();

                $hourlyData[$hour] = [
                    'hour' => (int)$hour,
                    'ok' => $ok,
                    'ng' => $ng,
                    'total' => $hourScans->count(),
                ];
            }
        }

        return array_values($hourlyData);
    }

    private function getEmptyHourlyStats()
    {
        $data = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $data[] = [
                'hour' => $hour,
                'ok' => 0,
                'ng' => 0,
                'total' => 0,
            ];
        }
        return $data;
    }
}
