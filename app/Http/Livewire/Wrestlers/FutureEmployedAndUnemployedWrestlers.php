<?php

namespace App\Http\Livewire\Wrestlers;

use App\Models\Wrestler;
use Livewire\Component;
use Livewire\WithPagination;

class FutureEmployedAndUnemployedWrestlers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $futureEmploymentAndUnemployedWrestlers = Wrestler::query()
            ->futureEmployment()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->paginate();

        return view('livewire.wrestlers.future-employed-and-unemployed-wrestlers', [
            'futureEmployedAndUnemployedWrestlers' => $futureEmploymentAndUnemployedWrestlers
        ]);
    }
}
