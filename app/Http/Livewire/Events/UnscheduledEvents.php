<?php

namespace App\Http\Livewire\Events;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Event;

class UnscheduledEvents extends BaseComponent
{
    use WithBulkActions, WithSorting;

    private $showDeleteModal = false;

    private $showFilters = false;

    private $filters = [
        'search' => '',
    ];

    public function deleteSelected()
    {
        $deleteCount = $this->selectedRowsQuery->count();

        $this->selectedRowsQuery->delete();

        $this->showDeleteModal = false;

        $this->notify('You\'ve deleted '.$deleteCount.' titles');
    }

    public function getRowsQueryProperty()
    {
        $query = Event::query()
            ->unscheduled();

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
        return view('livewire.events.unscheduled-events', [
            'unscheduledEvents' => $this->rows,
        ]);
    }
}
