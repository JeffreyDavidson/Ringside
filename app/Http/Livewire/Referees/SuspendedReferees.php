<?php

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\BaseComponent;
use App\Models\Referee;

class SuspendedReferees extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $suspendedReferees = Referee::query()
            ->suspended()
            ->withCurrentSuspendedAtDate()
            ->orderByCurrentSuspendedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.referees.suspended-referees', [
            'suspendedReferees' => $suspendedReferees,
        ]);
    }
}
