<?php

namespace App\Http\Livewire\Stables;

use App\Http\Livewire\BaseComponent;
use App\Models\Stable;

class ActiveStables extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $activeStables = Stable::query()
            ->active()
            ->withFirstActivatedAtDate()
            ->orderByFirstActivatedAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.stables.active-stables', [
            'activeStables' => $activeStables,
        ]);
    }
}
