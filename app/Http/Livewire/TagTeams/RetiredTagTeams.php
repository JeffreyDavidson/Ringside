<?php

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\BaseComponent;
use App\Models\TagTeam;

class RetiredTagTeams extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $retiredTagTeams = TagTeam::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.tagteams.retired-tagteams', [
            'retiredTagTeams' => $retiredTagTeams,
        ]);
    }
}
