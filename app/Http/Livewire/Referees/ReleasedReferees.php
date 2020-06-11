<?php

namespace App\Http\Livewire\Referees;

use App\Models\Referee;
use Livewire\Component;
use Livewire\WithPagination;

class ReleasedReferees extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.referees.released-referees', [
            'releasedReferees' => Referee::released()->withReleasedAtDate()->paginate($this->perPage)
        ]);
    }
}
