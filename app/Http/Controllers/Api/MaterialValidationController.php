<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ECL;
use App\Models\MaterialValidation;
use App\Models\PartNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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

            $partNumber = PartNumber::where('number', trim($request->part_number))->first();

            if ($request->filled('part_number') && !$partNumber) {
                $accessErrors[] = 'El número de parte no existe';
            }

            // Enviar datos a la API externa antes de crear el registro
            $externalApiResponse = null;
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Auth-Channel' => '0C5A15CC-DD57-4C4F-81DF-730AFA796967'
                ])
                    ->timeout(30) // Timeout de 30 segundos
                    ->post('http://192.168.130.16:8980/ykm-monitor/qualitylog', [
                        'barcode' => $request->final_label_code,
                        'status' => $request->validation_status,
                        'comments' => $request->validation_comment
                    ]);

                // Capturar la respuesta completa
                $externalApiResponse = [
                    'status_code' => $response->status(),
                    'response_body' => $response->json(),
                    'request_sent' => [
                        'barcode' => $request->final_label_code,
                        'status' => $request->validation_status,
                        'comments' => $request->validation_comment
                    ],
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s')
                ];

                // Verificar si la respuesta fue exitosa
                if (!$response->successful()) {
                    $accessErrors[] = 'Error al enviar datos a la API externa';
                    Log::warning('Error enviando datos a API externa', [
                        'status' => $response->status(),
                        'response' => $response->body(),
                        'request_data' => [
                            'barcode' => $request->final_label_code,
                            'status' => $request->validation_status,
                            'comments' => $request->validation_comment
                        ]
                    ]);
                }
            } catch (\Exception $e) {
                $accessErrors[] = 'Error de conexión con la API externa';

                // Capturar el error en la respuesta
                $externalApiResponse = [
                    'status_code' => null,
                    'response_body' => null,
                    'error' => $e->getMessage(),
                    'request_sent' => [
                        'barcode' => $request->final_label_code,
                        'status' => $request->validation_status,
                        'comments' => $request->validation_comment
                    ],
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s')
                ];

                Log::error('Error conectando con API externa', [
                    'error' => $e->getMessage(),
                    'request_data' => [
                        'barcode' => $request->final_label_code,
                        'status' => $request->validation_status,
                        'comments' => $request->validation_comment
                    ]
                ]);
            }

            // Crear el registro de validación
            $materialValidation = MaterialValidation::create([
                'user_id' => $user->id,
                'container_code' => $request->container_code,
                'visual_aid_code' => $request->visual_aid_code,
                'final_label_code' => $request->final_label_code,
                'part_number' => trim($request->part_number),
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
                    'external_api_response' => $externalApiResponse,
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

    /**
     * Validar secuencia de etiqueta final
     */
    public function validateSequence(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'final_label_code' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'isValid' => false,
                    'validationComment' => 'Orden Incorrecto',
                ], 422);
            }

            $finalLabelCode = $request->final_label_code;

            // Verificar longitud mínima
            if (strlen($finalLabelCode) <= 30) {
                return response()->json([
                    'isValid' => false,
                    'validationComment' => 'Orden Incorrecto',
                ]);
            }

            $exists = MaterialValidation::where('final_label_code', $finalLabelCode)->where('validation_status', 'OK')->exists();
            if ($exists) {
                return response()->json([
                    'isValid' => false,
                    'validationComment' => 'Registrado Anteriormente',
                ]);
            }

            // Descomponer la etiqueta final
            $order = substr($finalLabelCode, 0, 7);
            $sequence = substr($finalLabelCode, 7, 3);
            $partNumber = trim(substr($finalLabelCode, 10, 10));
            $quantity = substr($finalLabelCode, 20, 6);

            Log::info('Etiqueta final descompuesta', [
                'final_label_code' => $finalLabelCode,
                'order' => $order,
                'sequence' => $sequence,
                'part_number' => $partNumber,
                'quantity' => $quantity
            ]);

            // Consultar en ECL con la fecha actual
            $today = Carbon::now()->format('Ymd');
            Log::info('Consultando ECL con fecha', ['today' => $today, 'part_number' => $partNumber]);

            $eclRecords = ECL::select('CLIDNO', 'CLCARD', 'LPROD')
                ->whereRaw('TRIM(LPROD) LIKE ?', [trim($partNumber) . '%'])
                ->where('CLCARD', '>=', $today)
                ->orderBy('CLIDNO', 'asc')
                ->get();

            Log::info('Registros ECL encontrados', ['count' => $eclRecords->count()]);

            if ($eclRecords->isEmpty()) {
                return response()->json([
                    'isValid' => false,
                    'validationComment' => 'Secuencia Incorrecta',
                ]);
            }

            // Buscar el registro actual
            $currentRecord = null;
            $currentIndex = -1;

            foreach ($eclRecords as $index => $record) {
                if (strpos($record->CLIDNO, Carbon::now()->format('y') . $order) !== false) {
                    $currentRecord = $record;
                    $currentIndex = $index;
                    break;
                }
            }

            if (!$currentRecord) {
                return response()->json([
                    'isValid' => false,
                    'validationComment' => 'Secuencia Incorrecta',
                ]);
            }

            Log::info('Registro actual encontrado', [
                'CLIDNO' => $currentRecord->CLIDNO,
                'CLCARD' => $currentRecord->CLCARD,
                'index' => $currentIndex
            ]);

            // Buscar registro anterior
            if ($currentIndex > 0) {
                $previousRecord = $eclRecords[$currentIndex - 1];
                $previousCLIDNO = trim($previousRecord->CLIDNO);

                // Quitar los dos primeros dígitos
                $previousOrder = substr($previousCLIDNO, 2);

                Log::info('Registro anterior encontrado', [
                    'previousCLIDNO' => $previousCLIDNO,
                    'previousOrder' => $previousOrder
                ]);

                // Verificar en material_validations si el registro anterior fue escaneado
                $previousValidation = MaterialValidation::where('final_label_code', 'like', '%' . $previousOrder . '%')
                    ->where('validation_status', 'OK')
                    ->first();

                if (!$previousValidation) {
                    return response()->json([
                        'isValid' => false,
                        'validationComment' => 'Secuencia Incorrecta',
                    ]);
                }

                Log::info('Registro anterior validado', ['previous_validation_id' => $previousValidation->id]);
            } else {
                Log::info('No hay registro anterior, es el primer registro de la secuencia');
            }

            // Si pasa todas las validaciones
            return response()->json([
                'isValid' => true,
                'validationComment' => null,
                'parsedData' => [
                    'order' => $order,
                    'sequence' => $sequence,
                    'part_number' => $partNumber,
                    'quantity' => $quantity
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en validación de secuencia', [
                'error' => $e->getMessage(),
                'final_label_code' => $request->final_label_code ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'isValid' => false,
                'validationComment' => 'Secuencia Incorrecta',
            ], 500);
        }
    }
}
