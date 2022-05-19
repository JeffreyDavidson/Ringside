<?php

declare(strict_types=1);

namespace App\Http\Livewire\Titles;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Title;

class TitlesList extends BaseComponent
{
    use WithBulkActions, WithSorting;

    public $showFilters = false;

    public $filters = [
        'search' => '',
    ];

    public function getRowsQueryProperty()
    {
        $query = Title::query()
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
        return view('livewire.titles.titles-list', [
            'titles' => $this->rows,
        ]);
    }
}
