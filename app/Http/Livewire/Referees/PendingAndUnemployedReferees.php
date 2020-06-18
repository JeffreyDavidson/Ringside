<?php

namespace App\Http\Livewire\Referees;

use App\Models\Referee;
use Livewire\Component;
use Livewire\WithPagination;

class PendingAndUnemployedReferees extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $pendingAndUnemployedReferees = Referee::query()
            ->pendingEmployment()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->orderBy('last_name')
            ->paginate();

        return view('livewire.referees.pending-and-unemployed-referees', [
            'pendingAndUnemployedReferees' => $pendingAndUnemployedReferees
        ]);
    }
}
