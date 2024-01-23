<?php

declare(strict_types=1);

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Manager;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousTagTeamsList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Manager to use for component.
     */
    public Manager $manager;

    /**
     * Set the Manager to be used for this component.
     */
    public function mount(Manager $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->manager
            ->previousTagTeams();

        $query = $this->applySorting($query);

        $previousTagTeams = $query->paginate();

        return view('livewire.managers.previous-tag-teams.previous-tag-teams-list', [
            'previousTagTeams' => $previousTagTeams,
        ]);
    }
}
