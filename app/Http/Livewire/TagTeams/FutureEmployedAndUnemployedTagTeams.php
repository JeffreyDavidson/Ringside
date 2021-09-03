<?php

namespace App\Http\Livewire\TagTeams;

use App\Models\TagTeam;
use Livewire\Component;
use Livewire\WithPagination;

class FutureEmployedAndUnemployedTagTeams extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

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
