<?php

declare(strict_types=1);

namespace App\Http\Livewire\Venues;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Venue;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class VenuesList extends Component
{
    use WithPagination;
    use WithSorting;

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

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = Venue::query()
            ->when(
                $this->filters['search'],
                function (Builder $query, string $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('name');

        $query = $this->applySorting($query);

        $venues = $query->paginate();

        return view('livewire.venues.venues-list', [
            'venues' => $venues,
        ]);
    }
}
