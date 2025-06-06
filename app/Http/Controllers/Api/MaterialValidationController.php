<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialValidation;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MaterialValidationController extends Controller
{
    /**
     * Almacenar una nueva validación de material
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar los datos de entrada
            $validator = Validator::make($request->all(), [
                'work_center_id' => 'required|integer|exists:work_centers,id',
                'container_code' => 'required|string|max:255',
                'visual_aid_code' => 'required|string|max:255',
                'final_label_code' => 'required|string|max:255',
                'validation_status' => 'required|in:OK,NG',
                'part_number' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el usuario tenga acceso al centro de trabajo
            $workCenter = WorkCenter::find($request->work_center_id);
            $user = $request->user();

            if (!$user->workCenters()->where('work_center_id', $request->work_center_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este centro de trabajo'
                ], 403);
            }

            // Crear el registro de validación
            $materialValidation = MaterialValidation::create([
                'user_id' => $user->id,
                'work_center_id' => $request->work_center_id,
                'container_code' => $request->container_code,
                'visual_aid_code' => $request->visual_aid_code,
                'final_label_code' => $request->final_label_code,
                'part_number' => $request->part_number,
                'validation_status' => $request->validation_status,
                'validation_details' => [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'work_center_name' => $workCenter->name,
                    'work_center_number' => $workCenter->number,
                    'part_number' => $request->part_number,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

            // Log de la validación para auditoría
            Log::info('Material validation recorded', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'work_center_id' => $request->work_center_id,
                'work_center_name' => $workCenter->name,
                'validation_status' => $request->validation_status,
                'container_code' => $request->container_code,
                'visual_aid_code' => $request->visual_aid_code,
                'final_label_code' => $request->final_label_code,
                'part_number' => $request->part_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Validación registrada correctamente',
                'data' => [
                    'id' => $materialValidation->id,
                    'validation_status' => $materialValidation->validation_status,
                    'created_at' => $materialValidation->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing material validation', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener historial de validaciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $workCenterId = $request->query('work_center_id');
            $status = $request->query('status');
            $limit = $request->query('limit', 50);

            $query = MaterialValidation::with(['workCenter', 'user'])
                ->byUser($user->id)
                ->orderBy('created_at', 'desc');

            if ($workCenterId) {
                $query->byWorkCenter($workCenterId);
            }

            if ($status) {
                $query->byStatus($status);
            }

            $validations = $query->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $validations->map(function ($validation) {
                    return [
                        'id' => $validation->id,
                        'container_code' => $validation->container_code,
                        'visual_aid_code' => $validation->visual_aid_code,
                        'final_label_code' => $validation->final_label_code,
                        'validation_status' => $validation->validation_status,
                        'work_center' => [
                            'id' => $validation->workCenter->id,
                            'name' => $validation->workCenter->name,
                            'number' => $validation->workCenter->number,
                        ],
                        'created_at' => $validation->created_at,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching material validations', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de validaciones
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $workCenterId = $request->query('work_center_id');

            $query = MaterialValidation::byUser($user->id);

            if ($workCenterId) {
                $query->byWorkCenter($workCenterId);
            }

            $totalValidations = $query->count();
            $okValidations = $query->byStatus('OK')->count();
            $ngValidations = $query->byStatus('NG')->count();

            $successRate = $totalValidations > 0 ? ($okValidations / $totalValidations) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_validations' => $totalValidations,
                    'ok_validations' => $okValidations,
                    'ng_validations' => $ngValidations,
                    'success_rate' => round($successRate, 2),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching validation statistics', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas'
            ], 500);
        }
    }
}
