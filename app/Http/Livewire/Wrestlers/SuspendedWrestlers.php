<?php

namespace App\Http\Livewire\Wrestlers;

use App\Models\Wrestler;
use Livewire\Component;
use Livewire\WithPagination;

class SuspendedWrestlers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $suspendedWrestlers = Wrestler::query()
            ->suspended()
            ->withCurrentSuspendedAtDate()
            ->orderByCurrentSuspendedAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.wrestlers.suspended-wrestlers', [
            'suspendedWrestlers' => $suspendedWrestlers,
        ]);
    }
}
