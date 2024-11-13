<?php

declare(strict_types=1);

namespace App\Livewire\Stables;

use App\Models\Stable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PreviousTagTeamsList extends Component
{
    /**
     * Stable to use for component.
     */
    public Stable $stable;

    /**
     * Set the Stable to be used for this component.
     */
    public function mount(Stable $stable): void
    {
        $this->stable = $stable;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->stable
            ->previousTagTeams();

        $previousTagTeams = $query->paginate();

        return view('livewire.stables.previous-tag-teams.previous-tag-teams-list', [
            'previousTagTeams' => $previousTagTeams,
        ]);
    }
}
