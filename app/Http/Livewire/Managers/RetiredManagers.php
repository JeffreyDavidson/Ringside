<?php

namespace App\Http\Livewire\Managers;

use App\Models\Manager;
use Livewire\Component;
use Livewire\WithPagination;

class RetiredManagers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $retiredManagers = Manager::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.retired-managers', [
            'retiredManagers' => $retiredManagers,
        ]);
    }
}
