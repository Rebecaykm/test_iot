<?php

namespace App\Http\Controllers;

use App\Models\InforSyncSetting;
use App\Models\Line;
use Illuminate\Http\Request;

class InforSyncSettingController extends Controller
{
    /**
     * Muestra la configuración de sincronización a Infor (Live y Proto)
     */
    public function index()
    {
        $lines = Line::has('workCenters')
            ->with(['workCenters' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $settings = InforSyncSetting::all()->keyBy('environment');

        return view('infor-sync.index', [
            'lines' => $lines,
            'liveSetting' => $settings->get(InforSyncSetting::ENVIRONMENT_LIVE),
            'protoSetting' => $settings->get(InforSyncSetting::ENVIRONMENT_PROTO),
        ]);
    }

    /**
     * Guarda la configuración de ambos ambientes
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'live.enabled' => ['nullable', 'boolean'],
            'live.work_centers' => ['nullable', 'array'],
            'live.work_centers.*' => ['integer', 'exists:work_centers,id'],
            'proto.enabled' => ['nullable', 'boolean'],
            'proto.work_centers' => ['nullable', 'array'],
            'proto.work_centers.*' => ['integer', 'exists:work_centers,id'],
        ]);

        foreach ([InforSyncSetting::ENVIRONMENT_LIVE, InforSyncSetting::ENVIRONMENT_PROTO] as $environment) {
            InforSyncSetting::updateOrCreate(
                ['environment' => $environment],
                [
                    'enabled' => (bool) ($validated[$environment]['enabled'] ?? false),
                    'work_center_ids' => array_map('intval', $validated[$environment]['work_centers'] ?? []),
                ]
            );
        }

        return redirect()
            ->route('infor-sync.index')
            ->with('success', 'Configuración de sincronización guardada correctamente.');
    }
}
