<?php

declare(strict_types=1);

namespace App\Http\Livewire\Venues;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Venue;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;

class VenuesList extends BaseComponent
{
    use WithBulkActions;
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
     * Undocumented function.
     */
    public function getRowsQueryProperty(): Builder
    {
        $query = Venue::query()
            ->when($this->filters['search'], fn (Builder $query, string $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->oldest('name');

        return $this->applySorting($query);
    }

    /**
     * Undocumented function.
     */
    public function getRowsProperty(): LengthAwarePaginator
    {
        return $this->applyPagination($this->rowsQuery);
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.venues.venues-list', [
            'venues' => $this->rows,
        ]);
    }
}
