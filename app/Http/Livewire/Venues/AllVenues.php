<?php

namespace App\Http\Livewire\Venues;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\DataTable\WithBulkActions;
use App\Http\Livewire\DataTable\WithSorting;
use App\Models\Venue;

class AllVenues extends BaseComponent
{
    use WithBulkActions, WithSorting;

    public $showDeleteModal = false;

    public $showFilters = false;

    public $filters = [];

    public function deleteSelected()
    {
        $deleteCount = $this->selectedRowsQuery->count();

        $this->selectedRowsQuery->delete();

        $this->showDeleteModal = false;

        $this->notify('You\'ve deleted '.$deleteCount.' venues');
    }

    public function getRowsQueryProperty()
    {
        $query = Venue::query()
            ->orderBy('name');

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
        return view('livewire.venues.all-venues', [
            'venues' => $this->rows,
        ]);
    }
}
