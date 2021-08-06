<?php

namespace App\Http\Livewire\Referees;

use App\Models\Referee;
use Livewire\Component;
use Livewire\WithPagination;

class SuspendedReferees extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $suspendedReferees = Referee::query()
            ->suspended()
            ->withCurrentSuspendedAtDate()
            ->orderByCurrentSuspendedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.referees.suspended-referees', [
            'suspendedReferees' => $suspendedReferees,
        ]);
    }
}
