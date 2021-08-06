<?php

namespace App\Http\Livewire\Wrestlers;

use App\Models\Wrestler;
use Livewire\Component;
use Livewire\WithPagination;

class InjuredWrestlers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        $injuredWrestlers = Wrestler::query()
            ->injured()
            ->withCurrentInjuredAtDate()
            ->orderByCurrentInjuredAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.wrestlers.injured-wrestlers', [
            'injuredWrestlers' => $injuredWrestlers,
        ]);
    }
}
