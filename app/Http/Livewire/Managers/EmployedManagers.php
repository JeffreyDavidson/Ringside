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
        $employedManagers = Manager::query()
            ->employed()
            ->withFirstEmployedAtDate()
            ->orderByFirstEmployedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.employed-managers', [
            'employedManagers' => $employedManagers,
        ]);
    }
}
