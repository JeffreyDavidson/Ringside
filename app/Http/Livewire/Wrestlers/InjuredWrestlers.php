<?php

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\BaseComponent;
use App\Models\Wrestler;

class InjuredWrestlers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $injuredWrestlers = Wrestler::query()
            ->injured()
            ->withCurrentInjuredAtDate()
            ->orderByCurrentInjuredAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.wrestlers.injured-wrestlers', [
            'injuredWrestlers' => $injuredWrestlers,
        ]);
    }
}
