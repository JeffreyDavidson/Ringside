<?php

namespace App\Http\Livewire\TagTeams;

use App\Models\TagTeam;
use Livewire\Component;
use Livewire\WithPagination;

class PendingAndUnemployedTagTeams extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $pendingAndUnemployedTagTeams = TagTeam::query()
            ->pendingEmployment()
            ->orWhere
            ->unemployed()
            ->paginate();

        return view('livewire.tagteams.pending-and-unemployed-tagteams', [
            'pendingAndUnemployedTagTeams' => $pendingAndUnemployedTagTeams
        ]);
    }
}
