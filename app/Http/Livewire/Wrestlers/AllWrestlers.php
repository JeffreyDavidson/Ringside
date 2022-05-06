<?php

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Wrestler;

class AllWrestlers extends BaseComponent
{
    use WithBulkActions, WithSorting;

    protected $showFilters = false;

    protected $filters = [
        'search' => '',
    ];

    public function getRowsQueryProperty()
    {
        $query = Wrestler::query()
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
        return view('livewire.wrestlers.all-wrestlers', [
            'wrestlers' => $this->rows,
        ]);
    }
}
