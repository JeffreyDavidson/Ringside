<?php

namespace App\Http\Livewire\TagTeams;

use App\Models\TagTeam;
use Livewire\Component;
use Livewire\WithPagination;

class SuspendedTagTeams extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $suspendedTagTeams = TagTeam::query()
            ->suspended()
            ->withCurrentSuspendedAtDate()
            ->orderByCurrentSuspendedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.tagteams.suspended-tagteams', [
            'suspendedTagTeams' => $suspendedTagTeams,
        ]);
    }
}
