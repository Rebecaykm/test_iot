<?php

namespace App\Http\Controllers;

use App\Models\InforSyncSetting;
use App\Models\ItemClass;
use App\Models\Line;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InforSyncSettingController extends Controller
{
    private const ENVIRONMENTS = [InforSyncSetting::ENVIRONMENT_LIVE, InforSyncSetting::ENVIRONMENT_PROTO];

    /**
     * Muestra la configuración de sincronización a Infor (Live y Proto)
     */
    public function index()
    {
        $lines = Line::has('workCenters')
            ->with(['workCenters' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $settings = InforSyncSetting::with(['workCenters', 'itemClasses'])->get()->keyBy('environment');

        return view('infor-sync.index', [
            'lines' => $lines,
            'itemClasses' => ItemClass::orderBy('abbreviation')->get(),
            'liveSetting' => $settings->get(InforSyncSetting::ENVIRONMENT_LIVE),
            'protoSetting' => $settings->get(InforSyncSetting::ENVIRONMENT_PROTO),
        ]);
    }

    /**
     * Guarda la configuración de ambos ambientes. Cada estación seleccionada
     * requiere su fecha de inicio de envío (corte por fecha planeada). Las
     * clases se guardan por ambiente; sin ninguna, el ambiente no envía nada.
     */
    public function update(Request $request)
    {
        $rules = [];
        $messages = [];

        foreach (self::ENVIRONMENTS as $environment) {
            $rules["{$environment}.enabled"] = ['nullable', 'boolean'];
            $rules["{$environment}.item_classes"] = ['nullable', 'array'];
            $rules["{$environment}.item_classes.*"] = ['integer', 'exists:item_classes,id'];
            $rules["{$environment}.stations"] = ['nullable', 'array'];
            $rules["{$environment}.stations.*.selected"] = ['nullable', 'boolean'];
            $rules["{$environment}.stations.*.sync_from"] = [
                'nullable',
                "required_if_accepted:{$environment}.stations.*.selected",
                'date_format:Y-m-d',
            ];

            $messages["{$environment}.stations.*.sync_from.required_if_accepted"] = 'Ingresa la fecha de inicio de envío de la estación seleccionada.';
            $messages["{$environment}.stations.*.sync_from.date_format"] = 'La fecha de inicio de envío no es válida.';
        }

        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($validated) {
            foreach (self::ENVIRONMENTS as $environment) {
                $setting = InforSyncSetting::updateOrCreate(
                    ['environment' => $environment],
                    ['enabled' => (bool) ($validated[$environment]['enabled'] ?? false)]
                );

                $setting->itemClasses()->sync($validated[$environment]['item_classes'] ?? []);

                $syncDates = collect($validated[$environment]['stations'] ?? [])
                    ->filter(fn ($station) => filter_var($station['selected'] ?? false, FILTER_VALIDATE_BOOLEAN))
                    ->map(fn ($station) => $station['sync_from']);

                // Solo IDs de estaciones existentes (las llaves del arreglo vienen del formulario)
                $syncDates = $syncDates->only(
                    WorkCenter::whereIn('id', $syncDates->keys())->pluck('id')->all()
                );

                $setting->workCenters()->whereNotIn('work_center_id', $syncDates->keys())->delete();

                foreach ($syncDates as $workCenterId => $syncFrom) {
                    $workCenter = $setting->workCenters()->firstOrNew(['work_center_id' => $workCenterId]);

                    // Con fecha nueva el acumulado se vuelve a dividir al llegar esa fecha.
                    if ($workCenter->sync_from?->toDateString() !== $syncFrom) {
                        $workCenter->fill(['sync_from' => $syncFrom, 'split_at' => null])->save();
                    }
                }
            }
        });

        return redirect()
            ->route('infor-sync.index')
            ->with('success', 'Configuración de sincronización guardada correctamente.');
    }
}
