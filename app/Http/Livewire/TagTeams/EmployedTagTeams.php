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
        return view('livewire.tagteams.employed-tagteams', [
            'employedTagTeams' => TagTeam::employed()->withEmployedAtDate()->paginate($this->perPage)
        ]);
    }
}
