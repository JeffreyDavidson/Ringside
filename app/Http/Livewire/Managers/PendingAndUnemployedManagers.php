<?php

namespace App\Http\Livewire\Managers;

use App\Models\Manager;
use Livewire\Component;
use Livewire\WithPagination;

class PendingAndUnemployedManagers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $pendingAndUnemployedManagers = Manager::query()
            ->pendingEmployment()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->orderBy('last_name')
            ->paginate();

        return view('livewire.managers.pending-and-unemployed-managers', [
            'pendingAndUnemployedManagers' => $pendingAndUnemployedManagers
        ]);
    }
}
