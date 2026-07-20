<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    /**
     * Códigos de proyecto de Infor (IREF04) que agrupan varios tipos de proyecto.
     */
    public const INFOR_COMPOSITE_CODES = [
        '12' => ['1', '2'],
        '123' => ['1', '2', '3'],
        '13' => ['1', '3'],
        '23' => ['2', '3'],
        '45' => ['4', '5'],
        '56' => ['5', '6'],
        '47' => ['4', '7'],
        '57' => ['5', '7'],
        '79' => ['7', '9'],
        '710' => ['7', '10'],
        '811' => ['8', '11'],
    ];

    /**
     * Códigos de proyecto de Infor (IREF04) que corresponden directamente a un solo tipo.
     */
    public const INFOR_SINGLE_CODES = ['1', '2', '3', '4', '5', '7', '8', '9', '10', '11', '20', '3Y'];

    protected $fillable = [
        'type',
        'model',
        'prefix',
        'client_id'
    ];

    /**
     *
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     *
     */
    public function partNumbers(): BelongsToMany
    {
        return $this->belongsToMany(PartNumber::class, 'part_number_project', 'project_id', 'part_number_id');
    }

    /**
     * Devuelve los ids de los proyectos que corresponden a un código de
     * proyecto de Infor (IREF04), o null si el código no es reconocido.
     */
    public static function idsForInforCode(string $inforCode): ?array
    {
        $inforCode = trim($inforCode);

        $types = self::INFOR_COMPOSITE_CODES[$inforCode]
            ?? (in_array($inforCode, self::INFOR_SINGLE_CODES, true) ? [$inforCode] : null);

        if ($types === null) {
            return null;
        }

        return self::query()->whereIn('type', $types)->pluck('id')->all();
    }

    /**
     * Sincroniza los proyectos de un número de parte a partir de su código de
     * proyecto de Infor (IREF04). Devuelve false si el código no es reconocido;
     * en ese caso las asociaciones existentes no se modifican.
     */
    public static function syncPartNumberByInforCode(PartNumber $partNumber, string $inforCode): bool
    {
        $projectIds = self::idsForInforCode($inforCode);

        if ($projectIds === null) {
            return false;
        }

        $partNumber->projects()->sync($projectIds);

        return true;
    }
}
