<?php

declare(strict_types=1);

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Referee;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $rows
 * @property-read Builder $rowsQuery
 */
class PreviousMatchesList extends Component
{
    use WithPerPagePagination;
    use WithSorting;

    /**
     * Referee to use for component.
     */
    public Referee $referee;

    /**
     * List of filters that are allowed.
     *
     * @var array<string, string>
     */
    public array $filters = [
        'search' => '',
    ];

    /**
     * Set the Referee to be used for this component.
     */
    public function mount(Referee $referee): void
    {
        $this->referee = $referee;
    }

    /**
     * Run the query for this component.
     */
    #[Computed]
    public function rowsQuery(): Builder
    {
        $query = $this->referee
            ->previousMatches();

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
        return view('livewire.referees.previous-matches.previous-matches-list', [
            'previousMatches' => $this->rows,
        ]);
    }
}
