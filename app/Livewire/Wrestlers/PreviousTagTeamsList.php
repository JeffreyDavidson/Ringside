<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Livewire\Datatable\WithSorting;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousTagTeamsList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Wrestler to use for component.
     */
    public Wrestler $wrestler;

    /**
     * Set the Wrestler to be used for this component.
     */
    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->wrestler
            ->previousTagTeams();

        $query = $this->applySorting($query);

        $previousTagTeams = $query->paginate();

        return view('livewire.wrestlers.previous-tag-teams.previous-tag-teams-list', [
            'previousTagTeams' => $previousTagTeams,
        ]);
    }
}
