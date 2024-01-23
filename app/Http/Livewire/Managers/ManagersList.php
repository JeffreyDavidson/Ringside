<?php

declare(strict_types=1);

namespace App\Http\Livewire\Managers;

use App\Builders\ManagerBuilder;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Manager;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ManagersList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Determines if the filters should be shown.
     *
     * @var bool
     */
    public bool $showFilters = false;

    /**
     * Shows list of accepted filters and direction to be displayed.
     *
     * @var array<string, string>
     */
    public array $filters = [
        'search' => '',
    ];

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = Manager::query()
            ->when(
                $this->filters['search'],
                function (ManagerBuilder $query, string $search) {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('last_name');

        $query = $this->applySorting($query);

        $managers = $query->paginate();

        return view('livewire.managers.managers-list', [
            'managers' => $managers,
        ]);
    }
}
