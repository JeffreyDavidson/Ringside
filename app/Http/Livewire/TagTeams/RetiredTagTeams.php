<?php

namespace App\Http\Livewire\TagTeams;

use App\Models\TagTeam;
use Livewire\Component;
use Livewire\WithPagination;

class RetiredTagTeams extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.tagteams.retired-tagteams', [
            'retiredTagTeams' => TagTeam::retired()->withRetiredAtDate()->paginate($this->perPage)
        ]);
    }
}
