<?php

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\BaseComponent;
use App\Models\TagTeam;

class FutureEmployedAndUnemployedTagTeams extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $futureEmployedAndUnemployedTagTeams = TagTeam::query()
            ->futureEmployed()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->paginate();

        return view('livewire.tagteams.future-employed-and-unemployed-tagteams', [
            'futureEmployedAndUnemployedTagTeams' => $futureEmployedAndUnemployedTagTeams,
        ]);
    }
}
