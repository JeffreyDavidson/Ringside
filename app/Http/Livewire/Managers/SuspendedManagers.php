<?php

namespace App\Http\Livewire\Managers;

use App\Models\Manager;
use Livewire\Component;
use Livewire\WithPagination;

class SuspendedManagers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $suspendedManagers = Manager::query()
            ->suspended()
            ->withCurrentSuspendedAtDate()
            ->orderByCurrentSuspendedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.suspended-managers', [
            'suspendedManagers' => $suspendedManagers,
        ]);
    }
}
