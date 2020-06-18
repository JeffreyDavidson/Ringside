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
        $retiredTagTeams = TagTeam::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.tagteams.retired-tagteams', [
            'retiredTagTeams' => $retiredTagTeams
        ]);
    }
}
