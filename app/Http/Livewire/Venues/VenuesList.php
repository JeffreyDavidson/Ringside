<?php

declare(strict_types=1);

namespace App\Http\Livewire\Venues;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Venue;

class VenuesList extends BaseComponent
{
    use WithBulkActions;
    use WithSorting;

    public $showDeleteModal = false;

    public $showFilters = false;

    public $filters = [
        'search' => '',
    ];

    public function deleteSelected()
    {
        $deleteCount = $this->selectedRowsQuery->count();

        $this->selectedRowsQuery->delete();

        $this->showDeleteModal = false;
    }

    public function getRowsQueryProperty()
    {
        $query = Venue::query()
            ->when($this->filters['search'], fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->oldest('name');

        return $this->applySorting($query);
    }

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
        return view('livewire.venues.venues-list', [
            'venues' => $this->rows,
        ]);
    }
}
