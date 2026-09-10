<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PartNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'production_rate',
        'efficiency',
        'item_class_id',
        'work_center_id',
        'standard_pack_id',
        'standard_pack_quantity',
        'is_obsolete',
        'production_order'
    ];

    /**
     * Crea o actualiza el registro del número de parte a partir de los datos
     * de Infor y su renglón de routing (FRT), del cual toma el work center
     * y el production rate.
     */
    public static function storeFromInfor(string $number, ?string $name, ?ItemClass $itemClass, bool $isObsolete, ?object $routingMaster = null): self
    {
        $attributes = [
            'number' => $number,
            'name' => $name,
            'item_class_id' => $itemClass?->id,
            'is_obsolete' => $isObsolete,
        ];

        if ($routingMaster !== null) {
            $workCenter = WorkCenter::query()->where([['number', $routingMaster->workNumber], ['name', $routingMaster->workName]])->first();

            if ($workCenter !== null) {
                $attributes['work_center_id'] = $workCenter->id;
                $attributes['production_rate'] = $routingMaster->productionRate;
            }
        }

        $partNumber = self::query()->where([['number', $number], ['name', $name]])->first();

        if ($partNumber !== null) {
            $partNumber->update($attributes);

            return $partNumber;
        }

        return self::create($attributes);
    }

    /**
     * Scope para ordenar por orden de producción
     */
    public function scopeOrderByProduction($query)
    {
        return $query->orderBy('production_order', 'asc');
    }

    /**
     * Scope para obtener números de parte por work center ordenados
     */
    public function scopeByWorkCenterOrdered($query, $workCenterId)
    {
        return $query->where('work_center_id', $workCenterId)
            ->orderBy('production_order', 'asc');
    }

    /**
     * Método para obtener el siguiente número de parte en el orden de producción
     */
    public function getNextInProductionOrder()
    {
        return static::where('work_center_id', $this->work_center_id)
            ->where('production_order', '>', $this->production_order)
            ->orderBy('production_order', 'asc')
            ->first();
    }

    /**
     * Método para obtener el número de parte anterior en el orden de producción
     */
    public function getPreviousInProductionOrder()
    {
        return static::where('work_center_id', $this->work_center_id)
            ->where('production_order', '<', $this->production_order)
            ->orderBy('production_order', 'desc')
            ->first();
    }

    /**
     *
     */
    public function itemClass(): BelongsTo
    {
        return $this->belongsTo(ItemClass::class);
    }

    /**
     *
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    /**
     * Números de parte hijos (componentes) con los que se fabrica este número
     * de parte. Es el "proceso anterior": lo que se produce/ensambla antes de
     * llegar a este material. Estructura tomada de Infor LX (tabla MBM).
     */
    public function previousProcesses(): BelongsToMany
    {
        return $this->belongsToMany(
            PartNumber::class,
            'part_number_relations',
            'parent_part_number_id',
            'child_part_number_id'
        )
            ->using(PartNumberRelation::class)
            ->withPivot(['sequence_order', 'effective_date', 'discontinue_date', 'is_active', 'last_synced_at'])
            ->wherePivot('is_active', true)
            ->orderBy('part_number_relations.sequence_order');
    }

    /**
     * Números de parte padres (ensambles) en los que se utiliza este número de
     * parte. Es el "proceso siguiente": en qué se convierte/dónde se usa
     * después. Un mismo componente puede tener varios padres (se comparte
     * entre distintos ensambles).
     */
    public function nextProcesses(): BelongsToMany
    {
        return $this->belongsToMany(
            PartNumber::class,
            'part_number_relations',
            'child_part_number_id',
            'parent_part_number_id'
        )
            ->using(PartNumberRelation::class)
            ->withPivot(['sequence_order', 'effective_date', 'discontinue_date', 'is_active', 'last_synced_at'])
            ->wherePivot('is_active', true)
            ->orderBy('part_number_relations.sequence_order');
    }

    /**
     * Cadena completa hacia atrás (todos los componentes/hijos, multinivel:
     * hijos, nietos, bisnietos...) de este número de parte. Se cachea porque
     * recorrer un árbol BOM grande en cada carga de pantalla es costoso.
     */
    public function getFullPreviousChain(int $maxDepth = 20): array
    {
        return Cache::remember(
            "part_number_relations.previous_chain.{$this->id}",
            now()->addMinutes(30),
            fn () => $this->traversePartNumberRelations('parent_part_number_id', 'child_part_number_id', $maxDepth)
        );
    }

    /**
     * Cadena completa hacia adelante (todos los ensambles/padres, multinivel)
     * en los que termina usándose este número de parte.
     */
    public function getFullNextChain(int $maxDepth = 20): array
    {
        return Cache::remember(
            "part_number_relations.next_chain.{$this->id}",
            now()->addMinutes(30),
            fn () => $this->traversePartNumberRelations('child_part_number_id', 'parent_part_number_id', $maxDepth)
        );
    }

    /**
     * Árbol (anidado, no plano) de los procesos anteriores de este número de
     * parte, con los datos que necesita la vista de consulta pública:
     * nombre de estación, clase y cantidad de standard pack de cada nodo.
     */
    public function previousProcessTree(int $maxDepth = 20): array
    {
        return $this->buildProcessTree($this->getFullPreviousChain($maxDepth));
    }

    /**
     * Árbol (anidado) de los procesos siguientes de este número de parte.
     */
    public function nextProcessTree(int $maxDepth = 20): array
    {
        return $this->buildProcessTree($this->getFullNextChain($maxDepth));
    }

    /**
     * Convierte la lista plana de aristas (from -> to por nivel) en un árbol
     * anidado, cargando los datos de cada número de parte en una sola query
     * (whereIn) en vez de una por nodo.
     */
    protected function buildProcessTree(array $edges): array
    {
        $partIds = collect($edges)->pluck('part_number_id')->push($this->id)->unique()->values()->all();

        $details = static::with(['itemClass', 'workCenter'])
            ->whereIn('id', $partIds)
            ->get()
            ->keyBy('id');

        $childrenByParent = collect($edges)->groupBy('from_part_number_id');

        $buildNode = function ($id, array $ancestry) use (&$buildNode, $childrenByParent, $details) {
            $detail = $details->get($id);

            $node = [
                'id' => $id,
                'number' => $detail->number ?? null,
                'name' => $detail->name ?? null,
                'item_class' => optional($detail?->itemClass)->abbreviation,
                'item_class_id' => isset($detail->item_class_id) ? (int) $detail->item_class_id : null,
                'station' => optional($detail?->workCenter)->name,
                'station_number' => optional($detail?->workCenter)->number,
                'standard_pack_quantity' => $detail->standard_pack_quantity ?? null,
                'children' => [],
            ];

            // Protección extra contra ciclos en los datos de origen: si el nodo
            // ya está en la ascendencia actual, se corta aquí en vez de
            // recursionar infinitamente.
            if (in_array($id, $ancestry, true)) {
                return $node;
            }

            $ancestry[] = $id;

            foreach ($childrenByParent->get($id, []) as $edge) {
                $node['children'][] = $buildNode($edge['part_number_id'], $ancestry);
            }

            return $node;
        };

        return $buildNode($this->id, []);
    }

    /**
     * Recorre part_number_relations nivel por nivel (BFS): una sola consulta
     * por nivel en vez de una por nodo, con protección contra ciclos y un
     * límite de profundidad de seguridad.
     */
    protected function traversePartNumberRelations(string $fromColumn, string $toColumn, int $maxDepth): array
    {
        $visited = [$this->id => true];
        $frontier = [$this->id];
        $chain = [];
        $level = 0;

        while (!empty($frontier) && $level < $maxDepth) {
            $level++;

            $edges = DB::table('part_number_relations AS r')
                ->join('part_numbers AS p', 'p.id', '=', "r.$toColumn")
                ->whereIn("r.$fromColumn", $frontier)
                ->where('r.is_active', true)
                ->select("r.$fromColumn AS from_part_number_id", "r.$toColumn AS part_number_id", 'p.number', 'p.name', 'r.sequence_order')
                ->orderBy('r.sequence_order')
                ->get();

            $nextFrontier = [];

            foreach ($edges as $edge) {
                $chain[] = [
                    'level' => $level,
                    'from_part_number_id' => $edge->from_part_number_id,
                    'part_number_id' => $edge->part_number_id,
                    'number' => $edge->number,
                    'name' => $edge->name,
                ];

                if (!isset($visited[$edge->part_number_id])) {
                    $visited[$edge->part_number_id] = true;
                    $nextFrontier[] = $edge->part_number_id;
                }
            }

            $frontier = $nextFrontier;
        }

        return $chain;
    }

    /**
     *
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'part_number_project', 'part_number_id', 'project_id');
    }

    /**
     *
     */
    public function histories(): HasMany
    {
        return $this->hasMany(History::class, 'part_number_id');
    }

    /**
     * Historial de cambios de production_order (orden de producción en línea)
     */
    public function orderHistories(): HasMany
    {
        return $this->hasMany(ProductionOrderHistory::class, 'part_number_id');
    }

    /**
     *
     */
    public function scrapRecords(): HasMany
    {
        return $this->hasMany(ScrapRecord::class, 'part_number_id');
    }

    /**
     *
     */
    public function productionRecords(): HasMany
    {
        return $this->hasMany(ProductionRecord::class, 'part_number_id');
    }

    /**
     *
     */
    public function standardPack(): BelongsTo
    {
        return $this->belongsTo(StandardPack::class, 'standard_pack_id');
    }

    /**
     *
     */
    public function visualAids(): HasMany
    {
        return $this->hasMany(VisualAid::class, 'part_number_id');
    }

    /**
     *
     */
    public function activeImage(): HasOne
    {
        return $this->hasOne(VisualAid::class)->where('is_active', true);
    }

    /**
     * Get all custom attributes for this part number
     */
    public function customAttributes(): MorphMany
    {
        return $this->morphMany(Attribute::class, 'attributable');
    }

    /**
     * Get a specific custom attribute value
     */
    public function getCustomAttributeValue(string $key)
    {
        $attribute = $this->customAttributes()->where('key', $key)->first();
        return $attribute ? $attribute->value : null;
    }

    /**
     * Set a custom attribute value
     */
    public function setCustomAttributeValue(string $key, $value)
    {
        $this->customAttributes()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Recargar la relación después de modificar
        $this->load('customAttributes');
    }

    /**
     * Remove a custom attribute - VERSIÓN CORREGIDA
     */
    public function removeCustomAttribute(string $key): bool
    {
        // Usar la relación para eliminar
        $deleted = $this->customAttributes()
            ->where('key', $key)
            ->delete();

        // Forzar la recarga de la relación
        $this->load('customAttributes');

        return $deleted > 0;
    }

    /**
     * Check if custom attribute exists
     */
    public function hasCustomAttribute(string $key): bool
    {
        return $this->customAttributes()->where('key', $key)->exists();
    }

    /**
     * Get all custom attributes as key-value pairs - VERSIÓN CORREGIDA
     */
    public function getAllCustomAttributes(): array
    {
        // Usar la relación (query builder) en lugar de la colección cargada
        return $this->customAttributes()
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Devuelve un mapa [part_number_id => divisor] para convertir piezas a golpes,
     * calculado SOLO sobre los part numbers indicados (los que están en juego en
     * el turno/producción), no sobre todo el work center.
     *
     * Esto es clave: un troquel (atributo 'mid') puede estar registrado en part
     * numbers que ya no se corren (revisiones viejas, ej. BDWK34811A vs BDWK34811B).
     * Si esos contaran, el divisor se inflaría y los golpes saldrían más bajos.
     * Por eso el divisor solo suma el pieces_per_shot de los parts presentes aquí.
     *
     *     golpes = piezas_del_part / divisor_del_part
     *
     * Ej.: troquel BDWK34831/841 con BDWK34831 y BDWK34841 (ambos en el turno),
     * cada uno produjo 1423 piezas (pps=1) => divisor 2 => 1423/2 + 1423/2 = 1423.
     */
    public static function buildShotDivisorsForPartIds(array $partIds): array
    {
        $partIds = array_values(array_unique(array_filter($partIds)));

        if (empty($partIds)) {
            return [];
        }

        $parts = static::query()
            ->whereIn('id', $partIds)
            ->with(['customAttributes' => fn ($q) => $q->whereIn('key', ['mid', 'pieces_per_shot'])])
            ->get();

        return static::buildShotDivisors($parts);
    }

    /**
     * Construye el mapa [part_number_id => divisor] a partir de una colección de
     * PartNumber (distintos) que ya tienen cargados sus customAttributes ('mid' y
     * 'pieces_per_shot'). Ver buildShotDivisorsForPartIds() para el detalle.
     */
    public static function buildShotDivisors($parts): array
    {
        $piecesPerShot = [];
        $dieKey        = [];

        foreach ($parts as $part) {
            $attrs = $part->customAttributes->keyBy('key');

            $pps = max(1, (int) ($attrs['pieces_per_shot']->value ?? 1));
            $mid = trim((string) ($attrs['mid']->value ?? ''));

            $piecesPerShot[$part->id] = $pps;
            // Sin troquel => grupo propio para no mezclarlo con otros parts.
            $dieKey[$part->id] = $mid !== '' ? $mid : "part:{$part->id}";
        }

        // Suma de pieces_per_shot por troquel.
        $dieTotals = [];
        foreach ($piecesPerShot as $partId => $pps) {
            $key             = $dieKey[$partId];
            $dieTotals[$key] = ($dieTotals[$key] ?? 0) + $pps;
        }

        // Cada part hereda como divisor el total de su troquel.
        $divisors = [];
        foreach ($dieKey as $partId => $key) {
            $divisors[$partId] = max(1, $dieTotals[$key]);
        }

        return $divisors;
    }
}
