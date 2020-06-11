<?php

namespace App\Http\Livewire\Stables;

use App\Models\Stable;
use Livewire\Component;
use Livewire\WithPagination;

class RetiredStables extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.stables.retired-stables', [
            'retiredStables' => Stable::retired()->withRetiredAtDate()->paginate($this->perPage)
        ]);
    }
}
