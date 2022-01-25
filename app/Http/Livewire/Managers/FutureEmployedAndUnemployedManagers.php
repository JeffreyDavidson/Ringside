<?php

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\BaseComponent;
use App\Models\Manager;

class FutureEmployedAndUnemployedManagers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $futureEmployedAndUnemployedManagers = Manager::query()
            ->futureEmployed()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->orderBy('last_name')
            ->paginate();

        return view('livewire.managers.future-employed-and-unemployed-managers', [
            'futureEmployedAndUnemployedManagers' => $futureEmployedAndUnemployedManagers,
        ]);
    }
}
