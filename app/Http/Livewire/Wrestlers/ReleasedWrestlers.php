<?php

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\BaseComponent;
use App\Models\Wrestler;

class ReleasedWrestlers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
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
