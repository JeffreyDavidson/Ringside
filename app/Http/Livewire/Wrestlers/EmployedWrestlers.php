<?php

namespace App\Http\Livewire\Wrestlers;

use App\Models\Wrestler;
use Livewire\Component;
use Livewire\WithPagination;

class EmployedWrestlers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        return view('livewire.wrestlers.employed-wrestlers', [
            'employedWrestlers' => Wrestler::employed()->paginate($this->perPage)
        ]);
    }
}
