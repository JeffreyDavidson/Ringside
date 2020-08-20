<?php

namespace App\Http\Livewire\Managers;

use App\Models\Manager;
use Livewire\Component;
use Livewire\WithPagination;

class FutureEmployedAndUnemployedManagers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $futureEmployedAndUnemployedManagers = Manager::query()
            ->futureEmployment()
            ->orWhere
            ->unemployed()
            ->withFirstEmployedAtDate()
            ->orderByNullsLast('first_employed_at')
            ->orderBy('last_name')
            ->paginate();

        return view('livewire.managers.future-employed-and-unemployed-managers', [
            'futureEmployedAndUnemployedManagers' => $futureEmployedAndUnemployedManagers
        ]);
    }
}
