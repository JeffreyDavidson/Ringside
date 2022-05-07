<?php

namespace App\Http\Livewire\Stables;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Stable;

class StablesList extends BaseComponent
{
    use WithBulkActions, WithSorting;

    public $showFilters = false;

    public $filters = [
        'search' => '',
    ];

    public function getRowsQueryProperty()
    {
        $query = Stable::query()
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
        return view('livewire.stables.stables-list', [
            'stables' => $this->rows,
        ]);
    }
}
