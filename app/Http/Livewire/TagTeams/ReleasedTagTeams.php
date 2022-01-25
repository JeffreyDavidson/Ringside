<?php

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\BaseComponent;
use App\Models\TagTeam;

class ReleasedTagTeams extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $releasedTagTeams = TagTeam::released()
            ->withReleasedAtDate()
            ->paginate($this->perPage);

        return view('livewire.tagteams.released-tagteams', [
            'releasedTagTeams' => $releasedTagTeams,
        ]);
    }
}
