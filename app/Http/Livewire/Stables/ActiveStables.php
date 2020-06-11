<?php

namespace App\Http\Livewire\Stables;

use App\Models\Stable;
use Livewire\Component;
use Livewire\WithPagination;

class ActiveStables extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        return view('livewire.stables.active-stables', [
            'activeStables' => Stable::active()->withActivatedAtDate()->paginate($this->perPage)
        ]);
    }
}
