<?php

namespace App\Http\Livewire\Referees;

use App\Models\Referee;
use Livewire\Component;
use Livewire\WithPagination;

class FutureEmployedAndUnemployedReferees extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $futureEmployedAndUnemployedReferees = Referee::query()
            ->futureEmployment()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->orderBy('last_name')
            ->paginate();

        return view('livewire.referees.future-employed-and-unemployed-referees', [
            'futureEmployedAndUnemployedReferees' => $futureEmployedAndUnemployedReferees
        ]);
    }
}
