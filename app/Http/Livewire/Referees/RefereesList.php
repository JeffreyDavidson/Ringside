<?php

declare(strict_types=1);

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Referee;

/**
 * @property \Illuminate\Database\Eloquent\Collection $rows
 * @property \Illuminate\Database\Eloquent\Builder $rowsQuery
 */
class RefereesList extends BaseComponent
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
        $query = Referee::query()
            ->when($this->filters['search'], function ($query, $search) {
                $query->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%');
            })
            ->oldest('last_name');

        // dd($query);

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
        return view('livewire.referees.referees-list', [
            'referees' => $this->rows,
        ]);
    }
}
