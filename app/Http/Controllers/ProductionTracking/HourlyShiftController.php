<?php

namespace App\Http\Controllers\ProductionTracking;

use App\Http\Controllers\Controller;
use App\Services\ProductionTrackingService;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Http\Request;

class HourlyShiftController extends Controller
{
    public function __construct(
        // private readonly ProductionTrackingService $productionTrackingService,
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // $data = $this->productionTrackingService->getLabelProductionRecords(
        //     workcenterId: 79,
        //     plannedDate: new DateTimeImmutable('2026-08-27')
        // );

        // $products = $data['orders'];
        // $labels = $data['labels'];

        // $result = [];
        // $totalLabels = 0;
        // $completedLabels = 0;

        // foreach ($products as $product) {
        //     $orderLabels = array_filter($labels, function ($label) use ($product) {
        //         return $label['ProductCode'] === $product['ProductCode'];
        //     });

        //     usort($orderLabels, function ($a, $b) {
        //         return strtotime($a['ProductOrder']) <=> strtotime($b['ProductOrder']);
        //     });

        //     $totalPlanned = array_sum(
        //         array_map(
        //             fn ($label) => (int) $label['LabelQuantity'],
        //             $orderLabels
        //         )
        //     );

        //     $totalProduced = array_sum(
        //         array_map(
        //             fn ($label) => (int) $label['ProducedQuantity'],
        //             $orderLabels
        //         )
        //     );

        //     $result[] = [
        //         'WorkcenterName' => $product['WorkcenterName'],
        //         'ProductionSequence' => (int) $product['ProductionOrder'],
        //         'ProductCode' => $product['ProductCode'],
        //         'ClassName' => $product['ClassName'],
        //         'StandardPackNumber' => (int) $product['StandardPackNumber'],
        //         'LabelCount' => count($orderLabels),
        //         'CompletedPercentage' => $totalPlanned > 0
        //             ? min(($totalProduced / $totalPlanned) * 100, 100)
        //             : 0,
        //         'LabelsByShift' => collect($orderLabels)
        //             ->map(function ($label) {
        //                 return [
        //                     'ShiftId' => (int) $label['ShiftId'],
        //                     'LabelId' => (int) $label['LabelId'],
        //                     'LabelCode' => $label['LabelCode'],
        //                     'PlannedQuantity' => (int) $label['LabelQuantity'],
        //                     'ProducedQuantity' => (int) $label['ProducedQuantity'],
        //                     'ProgressPercentage' => (float) $label['ProgressPercentage'],
        //                     'LabelIsCompleted' => (bool) $label['LabelIsCompleted'],
        //                     'GlobalSequence' => (int) $label['GlobalSequence'],
        //                     'ExpectedCompletionDate' => date_create_immutable(
        //                         $label['ExpectedCompletionDate']
        //                     )->format('Y-m-d H:i:s'),
        //                     'ProductOrder' => (int) $label['ProductOrder'],
        //                     'ShopOrderNumber' => $label['ShopOrderNumber'],
        //                 ];
        //             })
        //             ->groupBy('ShiftId')
        //             ->union([
        //                 1 => collect(),
        //                 2 => collect(),
        //             ])
        //             ->sortKeys()
        //             ->toArray(),
        //         'LabelsByHour' => collect($orderLabels)
        //             ->map(function ($label) {
        //                 return [
        //                     'ShiftId' => (int) $label['ShiftId'],
        //                     'LabelId' => (int) $label['LabelId'],
        //                     'LabelCode' => $label['LabelCode'],
        //                     'PlannedQuantity' => (int) $label['LabelQuantity'],
        //                     'ProducedQuantity' => (int) $label['ProducedQuantity'],
        //                     'ProgressPercentage' => (float) $label['ProgressPercentage'],
        //                     'LabelIsCompleted' => (bool) $label['LabelIsCompleted'],
        //                     'GlobalSequence' => (int) $label['GlobalSequence'],
        //                     'ExpectedCompletionDate' => date_create_immutable(
        //                         $label['ExpectedCompletionDate']
        //                     )->format('Y-m-d H:i:s'),
        //                     'ProductOrder' => (int) $label['ProductOrder'],
        //                     'ShopOrderNumber' => $label['ShopOrderNumber'],
        //                 ];
        //             })
        //             ->groupBy(
        //                 fn ($item) => Carbon::parse($item['ExpectedCompletionDate'])
        //                     ->format('Ymd H')
        //             )
        //             ->sortKeys()
        //             ->toArray(),
        //     ];

        //     $totalLabels += count($orderLabels);
        //     $completedLabels += count(array_filter($orderLabels, fn ($label) => (bool) $label['LabelIsCompleted']));
        // }

        // // $result = [];

        // // foreach ($products as $product) {
        // //     $result[] = [
        // //         'product' => $product,
        // //         'labels' => array_map(function ($label) {
        // //             $label['hour'] = date_create_immutable($label['labelPlannedAt'])->format('Ymd H');

        // //             return $label;
        // //         }, array_values(array_filter($labels, fn ($label) => (int) $label['productId'] === (int) $product['productId']))),
        // //     ];
        // // }

        $data = file_get_contents('C:\\\\1\\hourly-shift.json');
        $data = json_decode($data, true);

        return view('production-tracking.hourly-shift', [
            'result' => $data['result'],
            'totalLabels' => $data['totalLabels'] ?? 0,
            'completedLabels' => $data['completedLabels'] ?? 0,
            'completedPercentage' => $data['completedPercentage'] ?? 0,
            // 'result' => $result,
            // 'totalLabels' => $totalLabels,
            // 'completedLabels' => $completedLabels,
            // 'completedPercentage' => round($totalLabels > 0 ? min(($completedLabels / $totalLabels) * 100, 100) : 0, 2),
        ]);
    }
}
