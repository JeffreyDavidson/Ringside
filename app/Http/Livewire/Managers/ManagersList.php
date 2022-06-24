<?php

declare(strict_types=1);

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Manager;

class ManagersList extends BaseComponent
{
    use WithBulkActions;
    use WithSorting;

    public $showFilters = false;

    public $filters = [
        'search' => '',
    ];

    public function getRowsQueryProperty()
    {
        $query = Manager::query()
            ->when($this->filters['search'], function ($query, $search) {
                $query->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%');
            })
            ->oldest('last_name');

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
        return view('livewire.managers.managers-list', [
            'managers' => $this->rows,
        ]);
    }
}
