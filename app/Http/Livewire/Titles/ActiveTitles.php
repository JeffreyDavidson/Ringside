<?php

namespace App\Http\Livewire\Titles;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\DataTable\WithBulkActions;
use App\Http\Livewire\DataTable\WithSorting;
use App\Models\Title;

class ActiveTitles extends BaseComponent
{
    use WithBulkActions, WithSorting;

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

        $this->notify('You\'ve deleted '.$deleteCount.' titles');
    }

    public function getRowsQueryProperty()
    {
        $query = Title::query()
            ->active()
            ->withFirstActivatedAtDate()
            ->orderByFirstActivatedAtDate()
            ->when($this->filters['search'], fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
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
        return view('livewire.titles.active-titles', [
            'activeTitles' => $this->rows,
        ]);
    }
}
