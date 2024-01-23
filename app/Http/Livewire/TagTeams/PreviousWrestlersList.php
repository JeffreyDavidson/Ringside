<?php

declare(strict_types=1);

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousWrestlersList extends Component
{
    use WithPagination;
    use WithSorting;

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

        $query = $this->applySorting($query);

        $previousWrestlers = $query->paginate();

        return view('livewire.tag-teams.previous-wrestlers.previous-wrestlers-list', [
            'previousWrestlers' => $previousWrestlers,
        ]);
    }
}
