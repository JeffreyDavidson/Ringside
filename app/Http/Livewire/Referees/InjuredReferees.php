<?php

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\BaseComponent;
use App\Models\Referee;

class InjuredReferees extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $injuredReferees = Referee::query()
            ->injured()
            ->withCurrentInjuredAtDate()
            ->orderByCurrentInjuredAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.referees.injured-referees', [
            'injuredReferees' => $injuredReferees,
        ]);
    }
}
