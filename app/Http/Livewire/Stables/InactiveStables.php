<?php

namespace App\Http\Livewire\Stables;

use App\Http\Livewire\BaseComponent;
use App\Models\Stable;

class InactiveStables extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
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
