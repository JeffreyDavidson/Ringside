<?php

namespace App\Http\Livewire\Wrestlers;

use App\Models\Wrestler;
use Livewire\Component;
use Livewire\WithPagination;

class ReleasedWrestlers extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $releasedWrestlers = Wrestler::query()
                ->released()
                ->withReleasedAtDate()
                ->paginate($this->perPage);

        return view('livewire.wrestlers.released-wrestlers', [
            'releasedWrestlers' => $releasedWrestlers,
        ]);
    }
}
