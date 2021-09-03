<?php

namespace App\Http\Livewire\Stables;

use App\Models\Stable;
use Livewire\Component;
use Livewire\WithPagination;

class FutureActivationAndUnactivatedStables extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $futureActivationAndUnactivatedStables = Stable::query()
            ->withFutureActivation()
            ->orWhere
            ->unactivated()
            ->withFirstActivatedAtDate()
            ->orderByNullsLast('first_activated_at')
            ->orderBy('name')
            ->paginate();

        return view('livewire.stables.future-activation-and-unactivated-stables', [
            'futureActivationAndUnactivatedStables' => $futureActivationAndUnactivatedStables,
        ]);
    }
}
