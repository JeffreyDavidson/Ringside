<?php

declare(strict_types=1);

namespace App\Http\Livewire\Venues;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Venue;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousEventsList extends Component
{
    use WithPagination;
    use WithSorting;

    public Venue $venue;

    /**
     * Determines if the filters should be shown.
     */
    public bool $showFilters = false;

    /**
     * Shows list of accepted filters and direction to be displayed.
     *
     * @var array<string, string>
     */
    public array $filters = [
        'search' => '',
    ];

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->venue
            ->previousEvents()
            ->when(
                $this->filters['search'],
                function (Builder $query, string $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('name');

        $query = $this->applySorting($query);

        $previousEvents = $query->paginate();

        return view('livewire.venues.previous-events.previous-events-list', [
            'previousEvents' => $previousEvents,
        ]);
    }
}
