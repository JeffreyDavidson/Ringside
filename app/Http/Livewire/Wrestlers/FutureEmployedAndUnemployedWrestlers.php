<?php

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\BaseComponent;
use App\Models\Wrestler;

class FutureEmployedAndUnemployedWrestlers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $futureEmploymentAndUnemployedWrestlers = Wrestler::query()
            ->futureEmployed()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->paginate();

        return view('livewire.wrestlers.future-employed-and-unemployed-wrestlers', [
            'futureEmployedAndUnemployedWrestlers' => $futureEmploymentAndUnemployedWrestlers,
        ]);
    }
}
