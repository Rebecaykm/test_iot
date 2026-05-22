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
                    ->post('http://192.168.130.46:8980/ykm-monitor/qualitylog', [
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

        // if (strlen($finalLabelCode) <= 30) {
        //     return response()->json([
        //         'isValid' => false,
        //         'validationComment' => 'Orden Incorrecto',
        //     ]);
        // }

        // $exists = MaterialValidation::where('final_label_code', $finalLabelCode)->where('validation_status', 'OK')->exists();
        // if ($exists) {
        //     return response()->json([
        //         'isValid' => false,
        //         'validationComment' => 'Registrado Anteriormente',
        //     ]);
        // }

        $allowedLines = ['Miniceldas', 'Index', 'InPanel J', 'Body Cross', 'Poka-Yoke'];
        if (count($userLines) === 1 && in_array($userLines[0], $allowedLines)) {
            try {
                // Descomponer la etiqueta final
                $order = substr($finalLabelCode, 0, 7);
                $sequenceFromLabel = substr($finalLabelCode, 7, 3);
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
                    if ($endDate->isWeekday()) {
                        $daysAdded++;
                    }
                }

                $formattedStartDate = $startDate->format('Y-m-d\T00:00:00');
                $formattedEndDate = $endDate->format('Y-m-d\T23:59:59');
                $currentYear = Carbon::now()->format('y');

                // Log::info('Consultando datos combinados ORDERS y BARCODES', [
                //     'start_date' => $formattedStartDate,
                //     'end_date' => $formattedEndDate,
                //     'part_number' => $partNumber,
                //     'current_year' => $currentYear
                // ]);

                $combinedData = DB::connection('dbEmba')
                    ->table('ORDERS')
                    ->join('BARCODES', 'ORDERS.ORDER_ID', '=', 'BARCODES.ORDER_ID')
                    ->select(
                        'ORDERS.ORDER_ID',
                        'ORDERS.DELIVERY_DATE',
                        'ORDERS.PART_ID',
                        'BARCODES.BARCODE_ID',
                        'BARCODES.BARCODE_E',
                        'BARCODES.BARCODE_M',
                        'BARCODES.SEQUENCE',
                        'BARCODES.SNP',
                        'BARCODES.STATUS',
                        'BARCODES.QTY',
                        'BARCODES.SCANNED_M'
                    )
                    ->whereRaw("RTRIM(LTRIM(ORDERS.PART_ID)) LIKE ?", [trim($partNumber)])
                    ->where('ORDERS.DELIVERY_DATE', '>=', $formattedStartDate)
                    ->where('BARCODES.BARCODE_M', 'like', $currentYear . '%')
                    ->where('ORDERS.ROUTE', 'NOT LIKE', 'W1')
                    ->orderBy('ORDERS.DELIVERY_DATE', 'asc')
                    ->orderBy('BARCODES.SEQUENCE', 'asc')
                    ->get();

                // Log::info('Registros combinados encontrados', ['count' => $combinedData->count()]);

                if ($combinedData->isEmpty()) {
                    return response()->json([
                        'isValid' => false,
                        'validationComment' => 'Secuencia Incorrecta',
                    ]);
                }

                // Procesar datos en memoria
                $ordersMap = [];
                foreach ($combinedData as $record) {
                    $orderId = $record->ORDER_ID;

                    if (!isset($ordersMap[$orderId])) {
                        $ordersMap[$orderId] = [
                            'order_id' => $orderId,
                            'delivery_date' => $record->DELIVERY_DATE,
                            'part_id' => $record->PART_ID,
                            'barcodes' => [],
                            'all_scanned' => true,
                            'sequences' => []
                        ];
                    }

                    // Convertir la secuencia de la BD a 3 dígitos
                    $threeDigitSequence = $this->convertSequenceToThreeDigits($record->SEQUENCE);

                    // Agregar información del barcode
                    $ordersMap[$orderId]['barcodes'][] = [
                        'barcode_id' => $record->BARCODE_ID,
                        'barcode_m' => $record->BARCODE_M,
                        'sequence' => $threeDigitSequence,
                        'original_sequence' => $record->SEQUENCE,
                        'scanned_m' => $record->SCANNED_M
                    ];

                    // Verificar si todos los códigos de barras están escaneados
                    if (empty($record->SCANNED_M)) {
                        $ordersMap[$orderId]['all_scanned'] = false;
                    }

                    // Almacenar secuencias para validación
                    $ordersMap[$orderId]['sequences'][] = $threeDigitSequence;
                }

                // Convertir a array y ordenar por fecha
                $orders = array_values($ordersMap);
                usort($orders, function ($a, $b) {
                    return strcmp($a['delivery_date'], $b['delivery_date']);
                });

                // Buscar la orden actual
                $currentOrder = null;
                $currentIndex = -1;

                foreach ($orders as $index => $orderRecord) {
                    if (strpos($orderRecord['order_id'], $order) !== false) {
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

                // Log::info('Orden actual encontrada', [
                //     'ORDER_ID' => $currentOrder['order_id'],
                //     'DELIVERY_DATE' => $currentOrder['delivery_date'],
                //     'all_scanned' => $currentOrder['all_scanned'],
                //     'index' => $currentIndex
                // ]);

                // VALIDACIÓN 1: Buscar órdenes anteriores no escaneadas
                if ($currentIndex > 0) {
                    // Recorrer TODAS las órdenes anteriores para encontrar la más antigua sin escanear
                    $oldestMissingOrder = null;

                    for ($i = $currentIndex - 1; $i >= 0; $i--) {
                        $previousOrder = $orders[$i];

                        // Si encontramos una orden con secuencias sin escanear
                        if (!$previousOrder['all_scanned']) {
                            // Guardar esta orden (la última que encontremos será la más antigua)
                            $oldestMissingOrder = $previousOrder;
                        }
                    }

                    // Si encontramos alguna orden sin escanear
                    if ($oldestMissingOrder) {
                        // Buscar la primera secuencia sin escanear en la orden más antigua
                        $missingSequence = null;

                        foreach ($oldestMissingOrder['barcodes'] as $barcode) {
                            if (empty($barcode['scanned_m'])) {
                                $missingSequence = $barcode['sequence'];
                                break; // Tomamos la primera secuencia sin escanear
                            }
                        }

                        Log::info('Orden más antigua no escaneada encontrada', [
                            'missing_order' => $oldestMissingOrder['order_id'],
                            'missing_sequence' => $missingSequence,
                            'all_sequences' => $oldestMissingOrder['sequences']
                        ]);

                        return response()->json([
                            'isValid' => false,
                            'validationComment' => 'Secuencia Incorrecta',
                            'expectedOrder' => $oldestMissingOrder['order_id'],
                            'displayMessage' => 'Falta escanear la secuencia: ' . $missingSequence . ' de la orden: ' . $oldestMissingOrder['order_id'],
                        ]);
                    }

                    // Log::info('Todas las órdenes anteriores han sido escaneadas correctamente');
                } else {
                    // Log::info('No hay órdenes anteriores, es la primera orden de la secuencia');
                }

                // VALIDACIÓN 2: Validar secuencias dentro de la orden actual
                $currentSequenceNumber = intval($sequenceFromLabel);

                // Log::info('Validando secuencias de la orden actual', [
                //     'sequence_from_label' => $sequenceFromLabel,
                //     'sequence_number' => $currentSequenceNumber,
                //     'barcodes_in_order' => count($currentOrder['barcodes'])
                // ]);

                // Si la secuencia escaneada no es la primera (001), validar que las anteriores estén escaneadas
                if ($currentSequenceNumber > 1) {
                    // Buscar la primera secuencia sin escanear en la orden actual
                    foreach ($currentOrder['barcodes'] as $barcode) {
                        $barcodeSequenceNumber = intval($barcode['sequence']);

                        // Solo verificar secuencias menores a la que se está escaneando
                        if ($barcodeSequenceNumber < $currentSequenceNumber) {
                            // Si esta secuencia no está escaneada
                            if (empty($barcode['scanned_m'])) {
                                Log::info('Secuencia anterior no escaneada en orden actual', [
                                    'missing_sequence' => $barcode['sequence'],
                                    'current_sequence' => $sequenceFromLabel,
                                    'order' => $order
                                ]);

                                return response()->json([
                                    'isValid' => false,
                                    'validationComment' => 'Secuencia Incorrecta',
                                    'expectedOrder' => $order,
                                    'displayMessage' => 'Falta escanear la secuencia: ' . $barcode['sequence'] . ' de la orden: ' . $order,
                                ]);
                            }
                        }
                    }

                    // Log::info('Todas las secuencias anteriores están escaneadas correctamente en la orden actual');
                }

                // Si pasa todas las validaciones
                return response()->json([
                    'isValid' => true,
                    'validationComment' => null,
                    'parsedData' => [
                        'order' => $order,
                        'sequence' => $sequenceFromLabel,
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

    /**
     * Convierte una secuencia de la BD a formato de 3 dígitos
     */
    private function convertSequenceToThreeDigits($sequence): string
    {
        $completeSequence = str_pad($sequence, 6, "0", STR_PAD_LEFT);
        $firstThree = substr((string) $completeSequence, 0, 3);
        return str_pad($firstThree, 3, '0', STR_PAD_LEFT);
    }
}
