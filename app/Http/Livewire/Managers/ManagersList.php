<?php

declare(strict_types=1);

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\BaseComponent;
use App\Http\Livewire\Datatable\WithBulkActions;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Manager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\View\View;

/**
 * @property \Illuminate\Database\Eloquent\Collection $rows
 * @property \Illuminate\Database\Eloquent\Builder $rowsQuery
 */
class ManagersList extends BaseComponent
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
     */
    public function getRowsQueryProperty(): Builder
    {
        $query = Manager::query()
            ->when($this->filters['search'], function ($query, $search) {
                $query->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%');
            })
            ->oldest('last_name');

        return $this->applySorting($query);
    }

    /**
     * Undocumented function.
     */
    public function getRowsProperty(): LengthAwarePaginator
    {
        return $this->applyPagination($this->rowsQuery);
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.managers.managers-list', [
            'managers' => $this->rows,
        ]);
    }
}
