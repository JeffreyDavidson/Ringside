<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PreviousWrestlersList extends Component
{
    /**
     * Tag Team to use for component.
     */
    public TagTeam $tagTeam;

    /**
     * Set the Tag Team to be used for this component.
     */
    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeam = $tagTeam;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->tagTeam
            ->previousWrestlers();

        $previousWrestlers = $query->paginate();

        return view('livewire.tag-teams.previous-wrestlers.previous-wrestlers-list', [
            'previousWrestlers' => $previousWrestlers,
        ]);
    }
}
