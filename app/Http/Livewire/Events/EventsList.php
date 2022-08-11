<?php

declare(strict_types=1);

namespace App\Http\Livewire\Events;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Event;

/**
 * @property \Illuminate\Database\Eloquent\Collection $rows
 * @property \Illuminate\Database\Eloquent\Builder $rowsQuery
 */
class EventsList extends BaseComponent
{
    use WithBulkActions;
    use WithSorting;

    /**
     * Determines if the filters should be shown.
     *
     * @var bool
     */
    public $showFilters = false;

    /**
     * Shows list of accepted filters and direction to be displayed.
     *
     * @var array<string, string>
     */
    public $filters = [
        'search' => '',
    ];

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getRowsQueryProperty()
    {
        $query = Event::query()
            ->when($this->filters['search'], fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->oldest('name');

        return $this->applySorting($query);
    }

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRowsProperty()
    {
        return $this->applyPagination($this->rowsQuery);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.events.events-list', [
            'events' => $this->rows,
        ]);
    }
}
