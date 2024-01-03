<?php

declare(strict_types=1);

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Manager;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $rows
 * @property-read Builder $rowsQuery
 */
class PreviousTagTeamsList extends Component
{
    use WithPerPagePagination;
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
     * Run the query for this component.
     */
    #[Computed]
    public function rowsQuery(): Builder
    {
        $query = $this->manager
            ->previousTagTeams();

        return $this->applySorting($query);
    }

    /**
     * Apply pagination to the component query results.
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->applyPagination($this->rowsQuery);
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.managers.previous-tag-teams.previous-tag-teams-list', [
            'previousTagTeams' => $this->rows,
        ]);
    }
}
