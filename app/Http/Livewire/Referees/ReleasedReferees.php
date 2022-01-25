<?php

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\BaseComponent;
use App\Models\Referee;

class ReleasedReferees extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $releasedReferees = Referee::query()
            ->released()
            ->withReleasedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.referees.released-referees', [
            'releasedReferees' => $releasedReferees,
        ]);
    }
}
