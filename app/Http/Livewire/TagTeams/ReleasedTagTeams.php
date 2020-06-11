<?php

namespace App\Http\Livewire\TagTeams;

use App\Models\TagTeam;
use Livewire\Component;
use Livewire\WithPagination;

class ReleasedTagTeams extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.tagteams.released-tagteams', [
            'releasedTagTeams' => TagTeam::released()->withReleasedAtDate()->paginate($this->perPage)
        ]);
    }
}
