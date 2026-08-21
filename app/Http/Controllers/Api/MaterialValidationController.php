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
                'mac_address' => 'nullable|string|max:255',
                'validation_comment' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos Incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            $partNumber = null;
            if ($request->filled('part_number')) {
                $partNumber = PartNumber::where('number', trim($request->part_number))->first();

                if (!$partNumber) {
                    $accessErrors[] = 'El número de parte no existe';
                }
            }

            $externalApiPayload = [
                'barcode' => $request->final_label_code,
                'status' => $request->validation_status,
                'comments' => $request->validation_comment,
            ];

            // Enviar datos a la API externa antes de crear el registro
            $externalApiResponse = null;
            try {
                $httpResponse = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Auth-Channel' => '0C5A15CC-DD57-4C4F-81DF-730AFA796967'
                ])
                    ->timeout(30) // Timeout de 30 segundos
                    ->post('http://192.168.130.46:8980/ykm-monitor/qualitylog', $externalApiPayload);

                // Capturar la respuesta completa
                $externalApiResponse = [
                    'status_code' => $httpResponse->status(),
                    'response_body' => $httpResponse->json(),
                    'request_sent' => $externalApiPayload,
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s')
                ];

                // Verificar si la respuesta fue exitosa
                if (!$httpResponse->successful()) {
                    $accessErrors[] = 'Error al enviar datos a la API';
                    Log::warning('Error al enviar datos a API', [
                        'status' => $httpResponse->status(),
                        'response' => $httpResponse->body(),
                        'request_data' => $externalApiPayload,
                    ]);
                }
            } catch (\Exception $e) {
                $accessErrors[] = 'Error de conexión con la API';

                // Capturar el error en la respuesta
                $externalApiResponse = [
                    'status_code' => null,
                    'response_body' => null,
                    'error' => $e->getMessage(),
                    'request_sent' => $externalApiPayload,
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s')
                ];

                Log::error('Error al conectar con API', [
                    'error' => $e->getMessage(),
                    'request_data' => $externalApiPayload,
                ]);
            }

            // Crear el registro de validación
            $materialValidation = MaterialValidation::create([
                'user_id' => $user->id,
                'container_code' => $request->container_code,
                'visual_aid_code' => $request->visual_aid_code,
                'final_label_code' => $request->final_label_code,
                'part_number' => $partNumber?->number ?? trim((string) $request->part_number),
                'validation_status' => $request->validation_status,
                'validation_comment' => $request->validation_comment,
                'validation_details' => [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'part_number' => $partNumber?->number,
                    'part_name' => $partNumber?->name,
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
                    ? 'Registrado correctamente'
                    : 'Registrado con error de acceso',
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
            return response()->json(['isValid' => false, 'validationComment' => 'Etiqueta final requerida'], 422);
        }

        $finalLabelCode = $request->final_label_code;

        $allowedLines = ['Miniceldas', 'Index', 'InPanel J', 'Body Cross', 'Poka-Yoke'];

        if (!empty(array_intersect($userLines, $allowedLines))) {
            try {
                // Identificar el tipo de etiqueta
                $data = $this->parseLabel($finalLabelCode);

                if ($data instanceof JsonResponse) {
                    return $data;
                }

                $label = $data['label'];

                // Extraer información de la etiqueta
                $labelType = $label['labelType'];
                $order = $label['order'];
                $sequenceFromLabel = $label['sequenceFromLabel'];
                $partNumber = $label['partNumber'];
                $quantity = $label['quantity'];

                Log::info('Validando secuencia de etiqueta', [
                        'label_type' => $labelType,
                        'order' => $order,
                        'sequence_from_label' => $sequenceFromLabel,
                        'part_number' => $partNumber,
                        'quantity' => $quantity,
                    ]);

                // Datos de embarque obtenidos de la base de datos
                $combinedData = $data['shipmentData'];

                if ($combinedData->isEmpty()) {
                    return response()->json([
                        'isValid' => false,
                        'validationComment' => 'No se encontraron órdenes para este número de parte',
                    ]);
                }

                // Agrupar por ORDER_ID y preparar la estructura de datos
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

                    // Extraer el número de secuencia real
                    $sequenceNumber = $this->extractSequenceNumber($record->SEQUENCE);
                    $isScanned = $this->isBarcodeScanned($labelType, $record);

                    // Agregar información del barcode
                    $ordersMap[$orderId]['barcodes'][] = [
                        'barcode_id' => $record->BARCODE_ID,
                        'sequence' => $sequenceNumber,
                        'original_sequence' => $record->SEQUENCE,
                        'is_scanned' => $isScanned,
                    ];

                    // Verificar si todos los códigos de barras están escaneados
                    if (!$isScanned) {
                        $ordersMap[$orderId]['all_scanned'] = false;
                    }

                    // Almacenar secuencias para validación
                    $ordersMap[$orderId]['sequences'][] = $sequenceNumber;
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
                        'validationComment' => 'Orden no encontrada',
                    ]);
                }
                $currentSequenceNumber = intval($sequenceFromLabel);

                // VALIDACIÓN 1: Verificar si la secuencia ya fue escaneada
                $currentBarcode = null;
                foreach ($currentOrder['barcodes'] as $barcode) {
                    if (intval($barcode['sequence']) === $currentSequenceNumber) {
                        $currentBarcode = $barcode;
                        break;
                    }
                }

                if ($currentBarcode && $currentBarcode['is_scanned']) {
                    return response()->json([
                        'isValid' => false,
                        'validationComment' => 'Etiqueta ya registrada',
                        'displayMessage' => 'La secuencia: ' . $sequenceFromLabel . ', de la orden: ' . $order . ', ya fue escaneada anteriormente',
                    ]);
                }

                // VALIDACIÓN 2: Buscar órdenes anteriores no escaneadas
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
                            if (!$barcode['is_scanned']) {
                                $missingSequence = $barcode['sequence'];
                                break; // Tomamos la primera secuencia sin escanear
                            }
                        }

                        // Log::info('Orden más antigua no escaneada encontrada', [
                        //     'missing_order' => $oldestMissingOrder['order_id'],
                        //     'missing_sequence' => $missingSequence,
                        //     'all_sequences' => $oldestMissingOrder['sequences']
                        // ]);

                        return response()->json([
                            'isValid' => false,
                            'validationComment' => 'Orden anterior sin escanear',
                            'expectedOrder' => $oldestMissingOrder['order_id'],
                            'displayMessage' => 'Falta escanear la secuencia: ' . $missingSequence . ', de la orden: ' . $oldestMissingOrder['order_id'],
                        ]);
                    }
                }

                // Si la secuencia escaneada no es la primera (001), validar que las anteriores estén escaneadas
                if ($currentSequenceNumber > 1) {
                    // Buscar la primera secuencia sin escanear en la orden actual
                    foreach ($currentOrder['barcodes'] as $barcode) {
                        $barcodeSequenceNumber = intval($barcode['sequence']);

                        // Solo verificar secuencias menores a la que se está escaneando
                        if ($barcodeSequenceNumber < $currentSequenceNumber) {
                            // Si esta secuencia no está escaneada
                            if (!$barcode['is_scanned']) {
                                // Log::info('Secuencia anterior no escaneada en orden actual', [
                                //     'missing_sequence' => $barcode['sequence'],
                                //     'current_sequence' => $sequenceFromLabel,
                                //     'order' => $order
                                // ]);

                                return response()->json([
                                    'isValid' => false,
                                    'validationComment' => 'Secuencia anterior sin escanear',
                                    'expectedOrder' => $order,
                                    'displayMessage' => 'Falta escanear la secuencia: ' . $barcode['sequence'] . ', de la orden: ' . $order,
                                ]);
                            }
                        }
                    }
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
                    'validationComment' => 'Error al validar la secuencia',
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
     * Extrae el número de secuencia real desde el campo SEQUENCE de la BD.
     * Se guarda como secuencia*1000 + total de la orden (ej. 1002 = secuencia
     * 1, total 2; 1006 = secuencia 1, total 6), así que basta con quedarnos
     * con lo que hay antes de los últimos 3 dígitos.
     */
    private function extractSequenceNumber($sequence): string
    {
        $sequenceNumber = intdiv((int) $sequence, 1000);
        return str_pad((string) $sequenceNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Determina si un barcode ya fue escaneado. MMVO lo marca llenando
     * SCANNED_M; el resto de las etiquetas lo reportan en STATUS, donde
     * 'CREATED' significa que todavía no se ha escaneado.
     */
    private function isBarcodeScanned(string $labelType, $record): bool
    {
        if ($labelType === 'MMVO') {
            return !empty($record->SCANNED_M);
        }

        return $record->STATUS !== 'CREATED';
    }

    /**
     * Ventana de fechas usada para consultar embarques: desde el viernes
     * anterior (si hoy es lunes) o desde hace 2 días, hasta hoy.
     */
    private function getShipmentDateRange(): array
    {
        $today = Carbon::now();

        $startDate = $today->isMonday()
            ? $today->copy()->previous(Carbon::FRIDAY)
            : $today->copy()->subDays(2);

        return [
            $startDate->format('Y-m-d\T00:00:00'),
            $today->format('y'),
        ];
    }

    /**
     * Consulta combinada ORDERS + BARCODES para un número de parte,
     * dentro de la ventana de fechas de embarque vigente.
     */
    private function fetchShipmentData(string $partNumber, bool $excludeRouteW1 = false)
    {
        [$formattedStartDate, $currentYear] = $this->getShipmentDateRange();

        $query = DB::connection('dbEmba')
            ->table('ORDERS AS O')
            ->join('BARCODES AS B', 'O.ORDER_ID', '=', 'B.ORDER_ID')
            ->select(
                'O.ORDER_ID',
                'O.DELIVERY_DATE',
                'O.PART_ID',
                'B.BARCODE_ID',
                'B.BARCODE_E',
                'B.BARCODE_M',
                'B.SCANNED_M',
                'B.SEQUENCE',
                'B.SNP',
                'B.STATUS',
                'B.QTY'
            )
            ->whereRaw('RTRIM(LTRIM(O.PART_ID)) LIKE ?', [trim($partNumber)])
            ->where('O.DELIVERY_DATE', '>=', $formattedStartDate)
            ->where('B.BARCODE_M', 'LIKE', $currentYear . '%')
            ->orderBy('O.DELIVERY_DATE', 'asc')
            ->orderBy('B.SEQUENCE', 'asc');

        if ($excludeRouteW1) {
            $query->where('O.ROUTE', 'NOT LIKE', 'W1');
        }

        return $query->get();
    }

    /**
     * Identificar el tipo de etiqueta y separar sus campos según su formato.
     * Devuelve un JsonResponse si la etiqueta no es válida o no tiene
     * lógica de separación implementada todavía.
     */
    private function parseLabel(string $finalLabelCode): array|JsonResponse
    {
        // Validación para etiquetas que contienen "TOYOTA 660B"
        if (str_contains($finalLabelCode, 'TMX')) {
            $label = [
                'labelType' => 'TOYOTA_660B',
                'order' => substr($finalLabelCode, 139, 10) . '  ' . substr($finalLabelCode, 38, 4),
                'sequenceFromLabel' => (string) intval(substr($finalLabelCode, 151, 4)),
                'partNumber' => trim(substr($finalLabelCode, 42, 12)),
                'quantity' => (string) intval(substr($finalLabelCode, 74, 5)),
            ];

            return [
                'label' => $label,
                'shipmentData' => $this->fetchShipmentData($label['partNumber']),
            ];
        }

        // Validación para etiquetas que contienen "TOYOTA 920B BC"
        if (str_contains($finalLabelCode, 'TMB')) {
            $label = [
                'labelType' => 'TOYOTA_920B_BC',
                'order' => substr($finalLabelCode, 139, 10) . '  ' . substr($finalLabelCode, 38, 4),
                'sequenceFromLabel' => (string) intval(substr($finalLabelCode, 151, 4)),
                'partNumber' => trim(substr($finalLabelCode, 42, 12) . '-BC'),
                'quantity' => (string) intval(substr($finalLabelCode, 74, 5)),
            ];

            return [
                'label' => $label,
                'shipmentData' => $this->fetchShipmentData($label['partNumber']),
            ];
        }

        // Validación para etiquetas que contienen "TOYOTA 920B GT"
        if (str_contains($finalLabelCode, 'TMG')) {
            $label = [
                'labelType' => 'TOYOTA_920B_GT',
                'order' => substr($finalLabelCode, 139, 10) . '  ' . substr($finalLabelCode, 38, 4),
                'sequenceFromLabel' => (string) intval(substr($finalLabelCode, 151, 4)),
                'partNumber' => trim(substr($finalLabelCode, 42, 12) . '-GT'),
                'quantity' => (string) intval(substr($finalLabelCode, 74, 5)),
            ];

            return [
                'label' => $label,
                'shipmentData' => $this->fetchShipmentData($label['partNumber']),
            ];
        }

        // Validación para etiquetas que contienen "J34A MNAO T1 DIRECTAS"
        if (str_contains($finalLabelCode, 'MTM')) {
            $label = [
                'labelType' => 'J34A_MNAO_T1_DIRECTAS',
                'order' => substr($finalLabelCode, 139, 10) . '  ' . substr($finalLabelCode, 38, 4),
                'sequenceFromLabel' => (string) intval(substr($finalLabelCode, 151, 4)),
                'partNumber' => trim(substr($finalLabelCode, 42, 12)),
                'quantity' => (string) intval(substr($finalLabelCode, 74, 5)),
            ];

            return [
                'label' => $label,
                'shipmentData' => $this->fetchShipmentData($label['partNumber']),
            ];
        }

        if (str_starts_with($finalLabelCode, 'T1')) {
            // TODO: agregar separación de campos para "J34A MNAO T2 INDIRECTAS"
        }

        // Validación para etiquetas que contienen "MMVO"
        if (strlen($finalLabelCode) >= 35 && strlen($finalLabelCode) <= 40) {
            $label = [
                'labelType' => 'MMVO',
                'order' => substr($finalLabelCode, 0, 7),
                'sequenceFromLabel' => substr($finalLabelCode, 7, 3),
                'partNumber' => trim(substr($finalLabelCode, 10, 10)),
                'quantity' => substr($finalLabelCode, 20, 6),
            ];

            return [
                'label' => $label,
                'shipmentData' => $this->fetchShipmentData($label['partNumber'], excludeRouteW1: true),
            ];
        }

        return response()->json([
            'isValid' => false,
            'validationComment' => 'Etiqueta no válida',
        ]);
    }
}
