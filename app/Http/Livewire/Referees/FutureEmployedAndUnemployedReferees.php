<?php

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\BaseComponent;
use App\Models\Referee;

class FutureEmployedAndUnemployedReferees extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $futureEmployedAndUnemployedReferees = Referee::query()
            ->futureEmployed()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->orderBy('last_name')
            ->paginate();

        return view('livewire.referees.future-employed-and-unemployed-referees', [
            'futureEmployedAndUnemployedReferees' => $futureEmployedAndUnemployedReferees,
        ]);
    }
}
