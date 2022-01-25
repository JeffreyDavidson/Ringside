<?php

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\BaseComponent;
use App\Models\Manager;

class InjuredManagers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $injuredManagers = Manager::query()
            ->injured()
            ->withCurrentInjuredAtDate()
            ->orderByCurrentInjuredAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.injured-managers', [
            'injuredManagers' => $injuredManagers,
        ]);
    }
}
