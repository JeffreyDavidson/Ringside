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
        return view('livewire.managers.retired-managers', [
            'retiredManagers' => Manager::retired()->withRetiredAtDate()->paginate($this->perPage)
        ]);
    }
}
