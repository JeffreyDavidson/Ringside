<?php

namespace App\Http\Livewire\Referees;

use App\Models\Referee;
use Livewire\Component;
use Livewire\WithPagination;

class RetiredReferees extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.referees.retired-referees', [
            'retiredReferees' => Referee::retired()->withRetiredAtDate()->paginate($this->perPage)
        ]);
    }
}
