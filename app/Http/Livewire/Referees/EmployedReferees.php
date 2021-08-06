<?php

namespace App\Http\Livewire\Referees;

use App\Models\Referee;
use Livewire\Component;
use Livewire\WithPagination;

class EmployedReferees extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $employedReferees = Referee::query()
            ->employed()
            ->withFirstEmployedAtDate()
            ->orderByFirstEmployedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.referees.employed-referees', [
            'employedReferees' => $employedReferees,
        ]);
    }
}
