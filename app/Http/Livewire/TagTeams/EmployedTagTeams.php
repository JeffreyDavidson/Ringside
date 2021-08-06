<?php

namespace App\Http\Livewire\TagTeams;

use App\Models\TagTeam;
use Livewire\Component;
use Livewire\WithPagination;

class EmployedTagTeams extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $employedTagTeams = TagTeam::query()
            ->employed()
            ->withFirstEmployedAtDate()
            ->orderByFirstEmployedAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.tagteams.employed-tagteams', [
            'employedTagTeams' => $employedTagTeams,
        ]);
    }
}
