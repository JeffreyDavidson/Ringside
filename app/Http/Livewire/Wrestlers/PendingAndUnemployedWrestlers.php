<?php

namespace App\Http\Livewire\Wrestlers;

use App\Models\Wrestler;
use Livewire\Component;
use Livewire\WithPagination;

class PendingAndUnemployedWrestlers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $pendingAndUnemployedWrestlers = Wrestler::query()
            ->pendingEmployment()
            ->orWhere
            ->unemployed()
            ->paginate();

        return view('livewire.wrestlers.pending-and-unemployed-wrestlers', [
            'pendingAndUnemployedWrestlers' => $pendingAndUnemployedWrestlers
        ]);
    }
}
