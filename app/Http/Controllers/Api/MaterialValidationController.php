<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ECL;
use App\Models\MaterialValidation;
use App\Models\PartNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
        $userLines = $request->user()->lines->pluck('name')->toArray();

        $validator = Validator::make($request->all(), [
            'final_label_code' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['isValid' => false, 'validationComment' => 'Orden Incorrecto'], 422);
        }

        $finalLabelCode = $request->final_label_code;

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

        if (count($userLines) === 1 && in_array('Bumper 59', $userLines)) {
            try {
                // Descomponer la etiqueta final
                $order = substr($finalLabelCode, 0, 7);
                $sequence = substr($finalLabelCode, 7, 3);
                $partNumber = trim(substr($finalLabelCode, 10, 10));
                $quantity = substr($finalLabelCode, 20, 6);

                $today = Carbon::now();
                $startDate = $today->isWeekday() && !$today->isMonday()
                    ? $today->copy()->subDay()
                    : $today->copy()->previous(Carbon::FRIDAY);

                $endDate = $today->copy();
                $daysAdded = 0;
                while ($daysAdded < 2) {
                    $endDate->addDay();
                    // Si no es fin de semana, contar como día hábil
                    if ($endDate->isWeekday()) {
                        $daysAdded++;
                    }
                }

                $formattedStartDate = $startDate->format('Y-m-d\T00:00:00');
                $formattedEndDate = $endDate->format('Y-m-d\T23:59:59');

                Log::info('Consultando ORDERS con rango de fechas', [
                    'start_date' => $formattedStartDate,
                    'end_date' => $formattedEndDate,
                    'part_number' => $partNumber
                ]);

                $orders = DB::connection('dbEmba')
                    ->table('ORDERS')
                    ->select('ORDER_ID', 'DELIVERY_DATE')
                    ->whereRaw("RTRIM(LTRIM(PART_ID)) LIKE ?", [trim($partNumber) . '%'])
                    ->whereBetween('DELIVERY_DATE', [$formattedStartDate, $formattedEndDate])
                    ->orderBy('DELIVERY_DATE', 'asc')
                    ->get();

                Log::info('Registros ORDERS encontrados', ['count' => $orders->count()]);

                if ($orders->isEmpty()) {
                    return response()->json([
                        'isValid' => false,
                        'validationComment' => 'Secuencia Incorrecta',
                    ]);
                }

                // Buscar la orden actual
                $currentOrder = null;
                $currentIndex = -1;

                foreach ($orders as $index => $orderRecord) {
                    if (strpos($orderRecord->ORDER_ID, $order) !== false) {
                        $currentOrder = $orderRecord;
                        $currentIndex = $index;
                        break;
                    }
                }

                if (!$currentOrder) {
                    return response()->json([
                        'isValid' => false,
                        'validationComment' => 'Secuencia Incorrecta',
                    ]);
                }

                Log::info('Orden actual encontrada', [
                    'ORDER_ID' => $currentOrder->ORDER_ID,
                    'DELIVERY_DATE' => $currentOrder->DELIVERY_DATE,
                    'index' => $currentIndex
                ]);
                dump($currentOrder->ORDER_ID);
                // Buscar órdenes anteriores no escaneadas
                // Buscar órdenes anteriores no escaneadas
                if ($currentIndex > 0) {
                    $mostRecentMissing = null;

                    // Revisar todas las órdenes anteriores desde la actual hacia atrás
                    for ($i = $currentIndex - 1; $i >= 0; $i--) {
                        $previousOrder = $orders[$i];
                        $previousOrderId = trim($previousOrder->ORDER_ID);

                        Log::info('Verificando orden anterior', ['previousOrderId' => $previousOrderId]);

                        // Consultar si la orden anterior fue escaneada
                        $barcodeRecord = DB::connection('dbEmba')
                            ->table('BARCODES')
                            ->where('BARCODE_M', 'like', '%25' . $previousOrderId . '%')
                            ->first();

                        if (!$barcodeRecord || empty($barcodeRecord->SCANNED_M)) {
                            // Orden no escaneada, guardamos esta como la más reciente faltante
                            $mostRecentMissing = $previousOrderId;
                            Log::info('Orden no escaneada encontrada', ['missing_order' => $previousOrderId]);
                        } else {
                            // Encontramos una orden escaneada, detenemos la búsqueda
                            Log::info('Orden escaneada encontrada', ['scanned_order' => $previousOrderId]);
                            break;
                        }
                    }

                    // Si encontramos una orden faltante, mostrar solo esa
                    if ($mostRecentMissing) {
                        return response()->json([
                            'isValid' => false,
                            'validationComment' => 'Secuencia Incorrecta',
                            'expectedOrder' => $mostRecentMissing,
                            'displayMessage' => 'Falta escanear la orden: ' . $mostRecentMissing,
                        ]);
                    }

                    Log::info('Todas las órdenes anteriores han sido escaneadas correctamente');
                } else {
                    Log::info('No hay órdenes anteriores, es la primera orden de la secuencia');
                }
                dd("fin");
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
        } else {
            return response()->json([
                'isValid' => true,
                'validationComment' => null,
            ]);
        }
    }
}
