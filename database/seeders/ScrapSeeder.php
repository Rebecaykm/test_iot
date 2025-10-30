<?php

namespace Database\Seeders;

use App\Models\Scrap;
use App\Models\TypeScrap;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScrapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primero crear los tipos de scrap
        TypeScrap::create(['name' => 'Defectos en la dimensión del producto']);
        TypeScrap::create(['name' => 'Defectos en la funcionalidad del producto']);
        TypeScrap::create(['name' => 'Defectos en el ensamble']);
        TypeScrap::create(['name' => 'Defectos en la pintura']);
        TypeScrap::create(['name' => 'Defectos en la soldadura']);
        TypeScrap::create(['name' => 'Defectos visuales']);
        TypeScrap::create(['name' => 'Defectos de materia prima']);
        TypeScrap::create(['name' => 'Defectos durante el control de manejo de material']);
        TypeScrap::create(['name' => 'Material de pruebas de ingeniería']);

        // Crear los scraps
        Scrap::create(['code' => '01', 'name' => 'Problema dimensional (pieza fuera de especificación dimensional, corte, superficie, diámetros, ángulo, posición)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '02', 'name' => 'Forma (deformación, doblez, torcido, golpes) barrenos, superficies, holes, contornos', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '03', 'name' => 'Grosor (espesor) del acero (espesor incorrecto, adelgazamiento, arrugas, arrastre)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '04', 'name' => 'Marca de scrap, incrustación de scrap', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '05', 'name' => 'Rebaba (barrenos, contorno, slots)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '06', 'name' => 'Rayaduras', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '07', 'name' => 'Faltante de material', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '08', 'name' => 'Exceso de material', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '09', 'name' => 'Mal estampado', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '10', 'name' => 'Faltante de barrenos', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '11', 'name' => 'Exceso de material', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '12', 'name' => 'Acero fin de rollo', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '13', 'name' => 'Acero principio de rollo', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '14', 'name' => 'Empalme de acero', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '15', 'name' => 'Mal estampado en marc de id (logo, lh, lr)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '16', 'name' => 'Mal formado por falta de nitrógeno', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '17', 'name' => 'Defecto de inclusión de materila en el acero', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '18', 'name' => 'Omisión del proceso (falta de un proceso, de material o tuerca)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '19', 'name' => 'Error en proceso de manufactura o ensamble', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la dimensión del producto')->pluck('id')->first()]);

        Scrap::create(['code' => '20', 'name' => 'Sonido extraño (vibración, flojo, sonido durante desempeño)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la funcionalidad del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '21', 'name' => 'Falta de la funcionalidad', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la funcionalidad del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '22', 'name' => 'Fuga', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la funcionalidad del producto')->pluck('id')->first()]);
        Scrap::create(['code' => '23', 'name' => 'Rígido (sin movimiento)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la funcionalidad del producto')->pluck('id')->first()]);

        Scrap::create(['code' => '24', 'name' => 'Objeto extraño (mezclado, adhesión, insertado)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);
        Scrap::create(['code' => '25', 'name' => 'Otorque insuficiente', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);
        Scrap::create(['code' => '26', 'name' => 'Doble ensamble', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);
        Scrap::create(['code' => '27', 'name' => 'Faltante de sello', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);
        Scrap::create(['code' => '28', 'name' => 'Falta de fuerza de inserción de bujes', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);
        Scrap::create(['code' => '29', 'name' => 'Tuerca eclipsada', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);
        Scrap::create(['code' => '30', 'name' => 'Defecto por pokayoke', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);
        Scrap::create(['code' => '31', 'name' => 'Golpe de pieza en jig', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en el ensamble')->pluck('id')->first()]);

        Scrap::create(['code' => '32', 'name' => 'Pintura negra (bajo espesor,mala adherencia)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);
        Scrap::create(['code' => '33', 'name' => 'Contaminación', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);
        Scrap::create(['code' => '34', 'name' => 'Pinhole', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);
        Scrap::create(['code' => '35', 'name' => 'Grumo', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);
        Scrap::create(['code' => '36', 'name' => 'Crater', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);
        Scrap::create(['code' => '37', 'name' => 'Mal retrabajo', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);
        Scrap::create(['code' => '38', 'name' => 'Mala apariencia (incusión de material en la pintura, blanqueamiento, manchas)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);
        Scrap::create(['code' => '39', 'name' => 'Falso contacto en pieza (falta de pintura)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la pintura')->pluck('id')->first()]);

        Scrap::create(['code' => '40', 'name' => 'Punto de soldado fuera de posición (spot weld sw , projection weld pw)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '41', 'name' => 'Defectos en punto de soldadura (fisura, posición, con polvo)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '42', 'name' => 'Soldadura defectuosa (posición, salpicado, fisura, socavado,', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '43', 'name' => 'Sin soldadura', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '44', 'name' => 'Soldadura desplazada arc weld aw', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '45', 'name' => 'Salpicadura de soldadura en tornillo tuerca, punzonados', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '46', 'name' => 'Baja resistencia en proyección', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '47', 'name' => 'Falta de penetración arc weld', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '48', 'name' => 'Corto en proyección de tuerca, tornillo, spacer', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '49', 'name' => 'Mal retrabajo de soldadura arc weld', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);
        Scrap::create(['code' => '50', 'name' => 'Mal retrabajo por machuelo o tarraja en cuerdas de tuerca o tornillo', 'type_scrap_id' => TypeScrap::where('name', 'Defectos en la soldadura')->pluck('id')->first()]);

        Scrap::create(['code' => '51', 'name' => 'Óxido (corrosión, fallas del recubrimiento anticorrosivo)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos visuales')->pluck('id')->first()]);
        Scrap::create(['code' => '52', 'name' => 'Fisura', 'type_scrap_id' => TypeScrap::where('name', 'Defectos visuales')->pluck('id')->first()]);

        Scrap::create(['code' => '53', 'name' => 'Material equivocado (mal etiquetado)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos de materia prima')->pluck('id')->first()]);
        Scrap::create(['code' => '54', 'name' => 'Composición de la materia prima', 'type_scrap_id' => TypeScrap::where('name', 'Defectos de materia prima')->pluck('id')->first()]);
        Scrap::create(['code' => '55', 'name' => 'Defecto de dimensión', 'type_scrap_id' => TypeScrap::where('name', 'Defectos de materia prima')->pluck('id')->first()]);
        Scrap::create(['code' => '56', 'name' => 'Deformación', 'type_scrap_id' => TypeScrap::where('name', 'Defectos de materia prima')->pluck('id')->first()]);
        Scrap::create(['code' => '57', 'name' => 'Óxido', 'type_scrap_id' => TypeScrap::where('name', 'Defectos de materia prima')->pluck('id')->first()]);
        Scrap::create(['code' => '58', 'name' => 'Problema de soldadura', 'type_scrap_id' => TypeScrap::where('name', 'Defectos de materia prima')->pluck('id')->first()]);

        Scrap::create(['code' => '59', 'name' => 'Mezcla con otro material u otra parte', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '60', 'name' => 'Falta de documentos (historial, estándar, identificación, trazabilidad)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '61', 'name' => 'Material caído (maniobras)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '62', 'name' => 'Otros', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '63', 'name' => 'Pruebas por parte de calidad: soldadura, macrosección y doblez (testing room)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '64', 'name' => 'Material golpeado (maniobras)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '65', 'name' => 'Validaciones por ajustes de ingeniería', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '66', 'name' => 'Validaciones por mantenimientos (correctivos, preventivos)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '67', 'name' => 'Material obsoleto por ecn (stock)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '68', 'name' => 'Sobreinventario de material (primeras entradas-primeras salidas)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);
        Scrap::create(['code' => '69', 'name' => 'Pruebas de validación de proceso "destructivas" (inicio, intermedio, final)', 'type_scrap_id' => TypeScrap::where('name', 'Defectos durante el control de manejo de material')->pluck('id')->first()]);

        Scrap::create(['code' => '70', 'name' => 'Pruebas / validaciones para nuevos proyectos/cambios (no se utiliza ningún componente de producción masiva)', 'type_scrap_id' => TypeScrap::where('name', 'Material de pruebas de ingeniería')->pluck('id')->first()]);
        Scrap::create(['code' => '71', 'name' => 'Pruebas / validaciones para nuevos proyectos/cambios (se utiliza componentes de producción masiva)', 'type_scrap_id' => TypeScrap::where('name', 'Material de pruebas de ingeniería')->pluck('id')->first()]);
    }
}
