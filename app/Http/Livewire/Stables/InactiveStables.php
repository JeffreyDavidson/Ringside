<?php

namespace App\Http\Livewire\Stables;

use App\Models\Stable;
use Livewire\Component;
use Livewire\WithPagination;

class InactiveStables extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $inactiveStables = Stable::query()
            ->inactive()
            ->withLastDeactivationDate()
            ->orderByLastDeactivationDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.stables.inactive-stables', [
            'inactiveStables' => $inactiveStables,
        ]);
    }
}
