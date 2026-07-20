<?php

namespace App\Http\Controllers;

use App\Models\MaterialValidation;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $status = $request->input('status');

        // Obtener las líneas del usuario autenticado
        $userLines = Auth::user()->lines->pluck('id')->toArray();

        // Si el usuario no tiene líneas asignadas, no mostrar nada
        if (empty($userLines)) {
            return view('material-validations.index', [
                'materialValidations' => \Illuminate\Pagination\LengthAwarePaginator::make([])
            ]);
        }

        $materialValidations = MaterialValidation::with(['workCenter', 'user', 'user.lines'])
            ->when($search, function ($query, $search) {
                // Agrupar las condiciones de búsqueda para no romper el filtro por líneas
                return $query->where(function ($q) use ($search) {
                    $q->where('container_code', 'like', "%{$search}%")
                        ->orWhere('visual_aid_code', 'like', "%{$search}%")
                        ->orWhere('final_label_code', 'like', "%{$search}%")
                        ->orWhere('part_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('nickname', 'like', "%{$search}%");
                        });
                });
            })
            ->when($date, function ($query, $date) {
                return $query->whereDate('created_at', $date);
            })
            ->when($status, function ($query, $status) {
                return $query->where('validation_status', $status);
            })
            // Filtrar por las líneas del usuario - corregido
            ->whereHas('user.lines', function ($query) use ($userLines) {
                $query->whereIn('lines.id', $userLines);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends([
                'search' => $search,
                'date' => $date,
                'status' => $status,
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
            // Obtener las líneas del usuario autenticado
            $userLines = Auth::user()->lines->pluck('id')->toArray();

            // Si el usuario no tiene líneas asignadas, mostrar estadísticas vacías
            if (empty($userLines)) {
                return $this->returnEmptyStatistics($request);
            }

            // Validar y obtener fecha
            $date = $request->input('date')
                ? Carbon::parse($request->input('date'))
                : Carbon::now();

            $dateStr = $date->toDateString();
            $isToday = $date->isToday();

            // Obtener turnos para mostrar
            $shiftsToShow = $this->getShiftsToShow($date, $isToday);

            // Obtener todos los escaneos del día seleccionado FILTRADOS POR LÍNEAS DEL USUARIO
            $scansToday = MaterialValidation::with(['user:id,name,nickname,profile_photo_path'])
                ->whereDate('created_at', $dateStr)
                ->whereHas('user.lines', function ($query) use ($userLines) {
                    $query->whereIn('lines.id', $userLines);
                })
                ->select('id', 'user_id', 'validation_status', 'validation_comment', 'created_at')
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
                $shiftStats = $this->getEmptyShiftStats($shiftsToShow['shifts'], $emptyStats);
                $userStats = collect();
                $hourlyStats = $this->getEmptyHourlyStats();
                $ngTypes = collect();
            } else {
                $dailyStats = $this->getDailyStats($scansToday);
                $shiftStats = $this->getShiftStats($shiftsToShow['shiftInfos'], $userLines);
                $userStats = $this->getUserStats($scansToday);
                $hourlyStats = $this->getHourlyStats($scansToday);
                $ngTypes = $this->getNgTypesStats($scansToday);
            }

            // CORRECCIÓN: Definir las variables antes de pasarlas al compact
            $shiftInfos = $shiftsToShow['shiftInfos'];
            $shifts = $shiftsToShow['shifts'];

            return view('material-validations.statistics', compact(
                'dailyStats',
                'shiftStats',
                'userStats',
                'hourlyStats',
                'shiftInfos',
                'shifts',
                'isToday',
                'ngTypes'
            ));
        } catch (\Exception $e) {
            // Log del error
            Log::error('Error en statistics: ' . $e->getMessage());

            // Redirigir con mensaje de error
            return redirect()->back()->with('error', 'Error al cargar las estadísticas. Inténtalo nuevamente.');
        }
    }

    /**
     * Retornar estadísticas vacías cuando el usuario no tiene líneas asignadas
     */
    private function returnEmptyStatistics(Request $request)
    {
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::now();

        $isToday = $date->isToday();
        $shiftsToShow = $this->getShiftsToShow($date, $isToday);

        $emptyStats = [
            'total' => 0,
            'ok' => 0,
            'ng' => 0,
            'ok_percentage' => 0,
            'ng_percentage' => 0
        ];

        $shiftInfos = $shiftsToShow['shiftInfos'];
        $shifts = $shiftsToShow['shifts'];

        return view('material-validations.statistics', [
            'dailyStats' => $emptyStats,
            'shiftStats' => $this->getEmptyShiftStats($shifts, $emptyStats),
            'userStats' => collect(),
            'hourlyStats' => $this->getEmptyHourlyStats(),
            'shiftInfos' => $shiftInfos,
            'shifts' => $shifts,
            'isToday' => $isToday,
            'ngTypes' => collect()
        ]);
    }

    private function getNgTypesStats($scans)
    {
        return $scans->where('validation_status', 'NG')
            ->groupBy('validation_comment')
            ->map(function ($ngScans, $comment) {
                return [
                    'comment' => $comment ?: 'Sin comentario',
                    'count' => $ngScans->count(),
                    'percentage' => 0
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->map(function ($item, $index) use ($scans) {
                $totalNg = $scans->where('validation_status', 'NG')->count();
                $item['percentage'] = $totalNg > 0 ? round(($item['count'] / $totalNg) * 100, 1) : 0;
                return $item;
            });
    }

    /**
     * Obtener los turnos que se deben mostrar según la fecha
     */
    private function getShiftsToShow($date, $isToday)
    {
        $shifts = [];
        $shiftInfos = [];

        if ($isToday) {
            // Para el día actual: mostrar turno anterior y turno actual
            $now = Carbon::now();
            $currentShift = $this->getCurrentShift($now);

            if ($currentShift) {
                $previousShift = $this->getPreviousShift($currentShift);

                // Agregar turno anterior primero
                if ($previousShift) {
                    $shifts[] = $previousShift;
                    $shiftInfos[] = $this->getPreviousShiftInfo($now, $previousShift);
                }

                // Agregar turno actual
                $shifts[] = $currentShift;
                $shiftInfos[] = $this->getCurrentShiftInfo($now, $currentShift);
            }
        } else {
            // Para días anteriores: mostrar turnos en orden cronológico
            $allShifts = Shift::orderBy('start_time', 'asc')->get();

            foreach ($allShifts as $shift) {
                $shifts[] = $shift;
                $shiftInfos[] = $this->getShiftInfoForDate($shift, $date);
            }
        }

        return [
            'shifts' => $shifts,
            'shiftInfos' => $shiftInfos
        ];
    }

    /**
     * Obtener el turno actual basado en la hora actual
     */
    private function getCurrentShift($now)
    {
        $shifts = Shift::all();
        $currentTime = $now->format('H:i:s');

        foreach ($shifts as $shift) {
            if ($this->isTimeInShift($currentTime, $shift)) {
                return $shift;
            }
        }

        return null;
    }

    /**
     * Verificar si una hora está dentro de un turno
     */
    private function isTimeInShift($time, $shift)
    {
        $startTime = $shift->start_time;
        $endTime = $shift->end_time;

        if ($startTime <= $endTime) {
            // Turno que no cruza medianoche
            return $time >= $startTime && $time <= $endTime;
        } else {
            // Turno que cruza medianoche
            return $time >= $startTime || $time <= $endTime;
        }
    }

    /**
     * Obtener el turno anterior al turno actual
     */
    private function getPreviousShift($currentShift)
    {
        $allShifts = Shift::orderBy('start_time', 'asc')->get();
        $currentIndex = $allShifts->search(function ($shift) use ($currentShift) {
            return $shift->id === $currentShift->id;
        });

        if ($currentIndex === false || $currentIndex === 0) {
            return $allShifts->last(); // Si es el primero, devolver el último
        }

        return $allShifts[$currentIndex - 1];
    }

    /**
     * Obtener información del turno anterior
     */
    private function getPreviousShiftInfo($now, $previousShift)
    {
        $timeRange = $this->getPreviousShiftTimeRange($previousShift, $now);

        return (object) [
            'shift' => $previousShift,
            'date' => $timeRange->startDateTime->toDateString(),
            'timeRange' => $timeRange,
            'label' => 'Turno Anterior'
        ];
    }

    /**
     * Obtener información del turno actual
     */
    private function getCurrentShiftInfo($now, $currentShift)
    {
        $timeRange = $this->getCurrentShiftTimeRange($currentShift, $now);

        return (object) [
            'shift' => $currentShift,
            'date' => $timeRange->startDateTime->toDateString(),
            'timeRange' => $timeRange,
            'label' => 'Turno Actual'
        ];
    }

    /**
     * Obtener información del turno para una fecha específica
     */
    private function getShiftInfoForDate($shift, $date)
    {
        $timeRange = $this->getShiftTimeRangeForDate($shift, $date);

        return (object) [
            'shift' => $shift,
            'date' => $date->toDateString(),
            'timeRange' => $timeRange,
            'label' => $shift->name
        ];
    }

    /**
     * Obtener rango de tiempo del turno anterior
     */
    private function getPreviousShiftTimeRange($shift, $now)
    {
        if ($shift->abbreviation === 'D') {
            // Turno diurno anterior
            $date = $now->copy()->subDay();
            $startDateTime = $date->copy()->setTimeFromTimeString($shift->start_time);
            $endDateTime = $date->copy()->setTimeFromTimeString($shift->end_time);
        } else {
            // Turno nocturno anterior
            if ($this->isNightShift($shift)) {
                $date = $now->copy()->subDay();
                $startDateTime = $date->copy()->setTimeFromTimeString($shift->start_time);
                $endDateTime = $date->copy()->addDay()->setTimeFromTimeString($shift->end_time);
            } else {
                $date = $now->copy()->subDay();
                $startDateTime = $date->copy()->setTimeFromTimeString($shift->start_time);
                $endDateTime = $date->copy()->setTimeFromTimeString($shift->end_time);
            }
        }

        return (object)[
            'shift' => $shift->abbreviation,
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime
        ];
    }

    /**
     * Obtener rango de tiempo del turno actual
     */
    private function getCurrentShiftTimeRange($shift, $now)
    {
        if ($shift->abbreviation === 'D') {
            // Turno diurno actual
            $startDateTime = $now->copy()->setTimeFromTimeString($shift->start_time);
            $endDateTime = $now->copy()->setTimeFromTimeString($shift->end_time);
        } else {
            // Turno nocturno actual
            if ($this->isNightShift($shift)) {
                $currentTime = $now->format('H:i:s');
                if ($currentTime >= $shift->start_time) {
                    // Estamos en la primera parte del turno nocturno
                    $startDateTime = $now->copy()->setTimeFromTimeString($shift->start_time);
                    $endDateTime = $now->copy()->addDay()->setTimeFromTimeString($shift->end_time);
                } else {
                    // Estamos en la segunda parte del turno nocturno
                    $startDateTime = $now->copy()->subDay()->setTimeFromTimeString($shift->start_time);
                    $endDateTime = $now->copy()->setTimeFromTimeString($shift->end_time);
                }
            } else {
                $startDateTime = $now->copy()->setTimeFromTimeString($shift->start_time);
                $endDateTime = $now->copy()->setTimeFromTimeString($shift->end_time);
            }
        }

        return (object)[
            'shift' => $shift->abbreviation,
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime
        ];
    }

    /**
     * Obtener rango de tiempo del turno para una fecha específica
     */
    private function getShiftTimeRangeForDate($shift, $date)
    {
        $referenceDate = Carbon::parse($date);

        if ($shift->abbreviation === 'D') {
            // Turno diurno
            $startDateTime = $referenceDate->copy()->setTimeFromTimeString($shift->start_time);
            $endDateTime = $referenceDate->copy()->setTimeFromTimeString($shift->end_time);
        } else {
            // Turno nocturno
            if ($this->isNightShift($shift)) {
                // El turno nocturno que terminó en la fecha consultada
                $startDateTime = $referenceDate->copy()->subDay()->setTimeFromTimeString($shift->start_time);
                $endDateTime = $referenceDate->copy()->setTimeFromTimeString($shift->end_time);
            } else {
                $startDateTime = $referenceDate->copy()->setTimeFromTimeString($shift->start_time);
                $endDateTime = $referenceDate->copy()->setTimeFromTimeString($shift->end_time);
            }
        }

        return (object)[
            'shift' => $shift->abbreviation,
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime
        ];
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
     * Obtener estadísticas de turnos
     */
    private function getShiftStats($shiftInfos, $userLines = [])
    {
        $stats = [];

        foreach ($shiftInfos as $shiftInfo) {
            $shiftScans = $this->getScansForShift($shiftInfo, $userLines);
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

    /**
     * Obtener estadísticas por usuario
     */
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
                    'ng_breakdown' => $this->getNgBreakdown($userScans)
                ];
            })
            ->filter() // Remover valores null
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Obtener estadísticas diarias
     */
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
            'ng_breakdown' => $this->getNgBreakdown($scans)
        ];
    }

    /**
     * Obtener escaneos para un turno específico FILTRADOS POR LÍNEAS DEL USUARIO
     */
    private function getScansForShift($shiftInfo, $userLines = [])
    {
        if (!$shiftInfo || !$shiftInfo->shift || !$shiftInfo->timeRange) {
            return collect();
        }

        return MaterialValidation::whereBetween('created_at', [
            $shiftInfo->timeRange->startDateTime,
            $shiftInfo->timeRange->endDateTime
        ])
            ->whereHas('user.lines', function ($query) use ($userLines) {
                $query->whereIn('lines.id', $userLines);
            })
            ->select('id', 'validation_status', 'validation_comment', 'created_at')
            ->get();
    }

    /**
     * Obtener estadísticas por hora
     */
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

    /**
     * Obtener estadísticas por hora vacías
     */
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

    /**
     * Obtener el desglose de tipos de NG
     */
    private function getNgBreakdown($scans)
    {
        $ngScans = $scans->where('validation_status', 'NG');

        if ($ngScans->isEmpty()) {
            return [];
        }

        return $ngScans->groupBy('validation_comment')
            ->map(function ($group, $comment) use ($ngScans) {
                $count = $group->count();
                $percentage = $ngScans->count() > 0 ? round(($count / $ngScans->count()) * 100, 2) : 0;

                return [
                    'comment' => $comment ?: 'Sin comentario',
                    'count' => $count,
                    'percentage' => $percentage
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    /**
     * Obtener el desglose de NG por turno
     */
    private function getShiftNgBreakdown($shiftInfos)
    {
        $breakdown = [];

        foreach ($shiftInfos as $shiftInfo) {
            $shiftScans = $this->getScansForShift($shiftInfo);
            $breakdown[$shiftInfo->shift->abbreviation] = $this->getNgBreakdown($shiftScans);
        }

        return $breakdown;
    }
}
