<?php

declare(strict_types=1);

namespace App\Http\Livewire\Stables;

use App\Builders\StableBuilder;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Stable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class StablesList extends Component
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
        $query = Stable::query()
            ->when(
                $this->filters['search'],
                function (StableBuilder $query, string $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('name');

        $query = $this->applySorting($query);

        $stables = $query->paginate();

        return view('livewire.stables.stables-list', [
            'stables' => $stables,
        ]);
    }
}
