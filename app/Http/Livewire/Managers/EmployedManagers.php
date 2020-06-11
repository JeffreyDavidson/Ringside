<?php

namespace App\Http\Livewire\Managers;

use App\Models\Manager;
use Livewire\Component;
use Livewire\WithPagination;

class EmployedManagers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        return view('livewire.managers.employed-managers', [
            'employedManagers' => Manager::employed()->withEmployedAtDate()->paginate($this->perPage)
        ]);
    }
}
