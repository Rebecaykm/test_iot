<?php

namespace App\Livewire\Guest;

use App\Models\PartNumber;
use Livewire\Component;

class PartNumberRelationsExplorer extends Component
{
    public string $partNumber = '';

    public string $direction = 'previous';

    public bool $searched = false;

    public ?array $part = null;

    public array $tree = [];

    protected function rules(): array
    {
        return [
            'partNumber' => ['required', 'string', 'max:35'],
            'direction' => ['required', 'in:previous,next'],
        ];
    }

    protected array $messages = [
        'partNumber.required' => 'Escribe un número de parte para buscar.',
    ];

    public function search(): void
    {
        $this->validate();

        $this->searched = true;

        $number = trim($this->partNumber);

        $partNumber = PartNumber::query()
            ->with(['itemClass', 'workCenter'])
            ->where('number', $number)
            ->first();

        if (!$partNumber) {
            $this->part = null;
            $this->tree = [];
            return;
        }

        $this->part = [
            'number' => $partNumber->number,
            'name' => $partNumber->name,
            'item_class' => optional($partNumber->itemClass)->abbreviation,
            'station' => optional($partNumber->workCenter)->name,
            'station_number' => optional($partNumber->workCenter)->number,
            'standard_pack_quantity' => $partNumber->standard_pack_quantity,
        ];

        $this->tree = $this->direction === 'next'
            ? $partNumber->nextProcessTree()
            : $partNumber->previousProcessTree();
    }

    public function render()
    {
        return view('livewire.guest.part-number-relations-explorer');
    }
}
