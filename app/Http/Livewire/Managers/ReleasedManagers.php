<?php

namespace App\Http\Livewire\Managers;

use App\Models\Manager;
use Livewire\Component;
use Livewire\WithPagination;

class ReleasedManagers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.managers.released-managers', [
            'releasedManagers' => Manager::released()->withReleasedAtDate()->paginate($this->perPage)
        ]);
    }
}
