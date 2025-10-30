<?php

namespace Database\Seeders;

use App\Models\LineStoppage;
use App\Models\TypeLineStoppage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LineStoppageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener los tipos
        $planeado = TypeLineStoppage::where('name', 'Planeado')->first()->id;
        $normales = TypeLineStoppage::where('name', 'Normales')->first()->id;
        $anormales = TypeLineStoppage::where('name', 'Anormales')->first()->id;

        // Paradas Planeadas
        $planeadas = [
            ['Chorei de inicio', 'Actividades de inicio de turno'],
            ['Break primero', 'Primer descanso programado'],
            ['Break segundo', 'Segundo descanso programado'],
            ['Comedor', 'Tiempo para almuerzo'],
            ['Box lunch', 'Tiempo para refrigerio'],
            ['Pruebas de ingeniería', 'Pruebas y validaciones de ingeniería'],
            ['Simulacros', 'Ejercicios de simulacro'],
            ['Limpieza de equipos', 'Limpieza programada de maquinaria'],
            ['Mantenimiento preventivo', 'Mantenimiento planificado'],
            ['Junta informativa mensual', 'Reunión mensual de información']
        ];

        foreach ($planeadas as $index => $parada) {
            LineStoppage::create([
                'code' => 'PLN-' . ($index + 1),
                'name' => $parada[0],
                'description' => $parada[1],
                'type_line_stoppage_id' => $planeado
            ]);
        }

        // Paradas Normales
        $normalesData = [
            ['Inspección de poka yoke', 'Verificación de dispositivos anti-error'],
            ['Check list de equipos', 'Inspección rutinaria de equipos'],
            ['Etiquetado', 'Actividades de etiquetado'],
            ['Validación de material', 'Verificación de materiales'],
            ['Cambio de modelo', 'Cambio entre diferentes modelos'],
            ['Cambio de caps', 'Cambio de cabezales o caps'],
            ['Cambio de electrodos', 'Reemplazo de electrodos'],
            ['Cambio de micro alambre', 'Cambio de alambre de soldadura']
        ];

        foreach ($normalesData as $index => $parada) {
            LineStoppage::create([
                'code' => 'NOR-' . ($index + 1),
                'name' => $parada[0],
                'description' => $parada[1],
                'type_line_stoppage_id' => $normales
            ]);
        }

        // Paradas Anormales
        $anormalesData = [
            ['Faltante de material', 'Falta de material en línea'],
            ['Espera de material', 'Espera por llegada de material'],
            ['Faltante de contenedor', 'Falta de contenedores'],
            ['Espera por contenedor', 'Espera por contenedores'],
            ['Idas al baño/enfermería/agua', 'Ausencias personales del operador'],
            ['Espera por instrucciones de líder', 'Espera por dirección del supervisor'],
            ['Espera por liberación de estación', 'Espera para liberar estación de trabajo'],
            ['Campaña de calidad', 'Actividades extraordinarias de calidad'],
            ['Juntas de retroalimentación', 'Reuniones de feedback no planificadas'],
            ['Fallas de sensor', 'Fallos en sensores de equipos'],
            ['Fallas en proceso teaching', 'Problemas en procesos de enseñanza'],
            ['Falla en clamp', 'Fallos en dispositivos de sujeción'],
            ['Falla en la programación', 'Errores en programación de equipos'],
            ['Falta de etiquetas', 'Falta de etiquetas para producción'],
            ['Cambio de contenedor vacío/lleno', 'Cambio no programado de contenedores'],
            ['Cambio de navajas', 'Reemplazo de cuchillas o herramientas'],
            ['Falla de controlador', 'Fallos en controladores de equipos'],
            ['Tip tapado', 'Boquillas o tips obstruidos'],
            ['Calentamiento de holder', 'Sobrecalentamiento de soportes'],
            ['Baja resistencia', 'Problemas de resistencia en materiales']
        ];

        foreach ($anormalesData as $index => $parada) {
            LineStoppage::create([
                'code' => 'ANM-' . ($index + 1),
                'name' => $parada[0],
                'description' => $parada[1],
                'type_line_stoppage_id' => $anormales
            ]);
        }
    }
}
