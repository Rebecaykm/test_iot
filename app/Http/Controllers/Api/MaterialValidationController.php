<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialValidation;
use App\Models\PartNumber;
use Carbon\Carbon;
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
            $accessErrors = [];

            $validator = Validator::make($request->all(), [
                'container_code' => 'required|string|max:255',
                'visual_aid_code' => 'required|string|max:255',
                'final_label_code' => 'required|string|max:255',
                'validation_status' => 'required|in:OK,NG',
                'part_number' => 'nullable|string|max:255',
                'device_model' => 'nullable|string|max:255',
                'device_name' => 'nullable|string|max:255',
                'device_id' => 'nullable|string|max:255',
                'ip_address' => 'nullable|string|max:45',
                'validation_comment' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $accessErrors[] = 'Datos de validación incorrectos';
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            $partNumber = PartNumber::where('number', $request->part_number)->first();

            if ($request->filled('part_number') && !$partNumber) {
                $accessErrors[] = 'El número de parte no existe';
            }

            // Crear el registro de validación
            $materialValidation = MaterialValidation::create([
                'user_id' => $user->id,
                'container_code' => $request->container_code,
                'visual_aid_code' => $request->visual_aid_code,
                'final_label_code' => $request->final_label_code,
                'part_number' => $request->part_number,
                'validation_status' => $request->validation_status,
                'validation_comment' => $request->validation_comment,
                'validation_details' => [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'part_number' => $partNumber ? $partNumber->number : null,
                    'part_name' => $partNumber ? $partNumber->name : null,
                    'device_model' => $request->device_model,
                    'device_name' => $request->device_name,
                    'device_id' => $request->device_id,
                    'ip_address' => $request->ip_address,
                    'mac_address' => $request->mac_address,
                    'access_errors' => $accessErrors,
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            ]);

            $response = [
                'success' => empty($accessErrors),
                'message' => empty($accessErrors)
                    ? 'Validación registrada correctamente'
                    : 'Validación registrada con errores de acceso',
                'data' => [
                    'id' => $materialValidation->id,
                    'validation_status' => $materialValidation->validation_status,
                    'created_at' => $materialValidation->created_at,
                ]
            ];

            if (!empty($accessErrors)) {
                $response['access_errors'] = $accessErrors;
            }

            return response()->json($response, 201);
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
            $status = $request->query('validation_status');
            $limit = $request->query('limit', 50);

            // Calcular inicio y fin de la semana actual
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();

            $query = MaterialValidation::with(['workCenter', 'user'])
                ->byUser($user->id)
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->orderBy('created_at', 'desc');

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
}
