<?php

namespace App\Http\Livewire\Managers;

use App\Models\Manager;
use Livewire\Component;
use Livewire\WithPagination;

class InjuredManagers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $injuredManagers = Manager::query()
            ->injured()
            ->withCurrentInjuredAtDate()
            ->orderByCurrentInjuredAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.injured-managers', [
            'injuredManagers' => $injuredManagers,
        ]);
    }
}
