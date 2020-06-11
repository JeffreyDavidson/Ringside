<?php

namespace App\Http\Livewire\Wrestlers;

use App\Models\Wrestler;
use Livewire\Component;
use Livewire\WithPagination;

class RetiredWrestlers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.wrestlers.retired-wrestlers', [
            'retiredWrestlers' => Wrestler::retired()->withRetiredAtDate()->paginate($this->perPage)
        ]);
    }
}
