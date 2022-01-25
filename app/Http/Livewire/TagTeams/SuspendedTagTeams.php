<?php

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\BaseComponent;
use App\Models\TagTeam;

class SuspendedTagTeams extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $suspendedTagTeams = TagTeam::query()
            ->suspended()
            ->withCurrentSuspendedAtDate()
            ->orderByCurrentSuspendedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.tagteams.suspended-tagteams', [
            'suspendedTagTeams' => $suspendedTagTeams,
        ]);
    }
}
