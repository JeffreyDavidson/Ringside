<?php

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\BaseComponent;
use App\Models\Wrestler;

class SuspendedWrestlers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $suspendedWrestlers = Wrestler::query()
            ->orderBy('name')
            ->suspended()
            ->withCurrentSuspendedAtDate()
            ->orderByCurrentSuspendedAtDate()
            ->paginate($this->perPage);

        return view('livewire.wrestlers.suspended-wrestlers', [
            'suspendedWrestlers' => $suspendedWrestlers,
        ]);
    }
}
