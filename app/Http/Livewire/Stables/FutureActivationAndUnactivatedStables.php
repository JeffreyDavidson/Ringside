<?php

namespace App\Http\Livewire\Stables;

use App\Http\Livewire\BaseComponent;
use App\Models\Stable;

class FutureActivationAndUnactivatedStables extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $futureActivationAndUnactivatedStables = Stable::query()
            ->withFutureActivation()
            ->orWhere
            ->unactivated()
            ->withFirstActivatedAtDate()
            ->orderByNullsLast('first_activated_at')
            ->orderBy('name')
            ->paginate();

        return view('livewire.stables.future-activation-and-unactivated-stables', [
            'futureActivationAndUnactivatedStables' => $futureActivationAndUnactivatedStables,
        ]);
    }
}
