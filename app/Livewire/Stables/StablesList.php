<?php

declare(strict_types=1);

namespace App\Livewire\Stables;

use App\Builders\StableBuilder;
use App\Livewire\Datatable\WithSorting;
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
     * @var array<int>
     */
    public array $selectedStableIds = [];

    /**
     * @var array<int>
     */
    public array $stableIdsOnPage = [];

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

        $this->stableIdsOnPage = $stables->map(fn (Stable $stable) => (string) $stable->id)->toArray();

        return view('livewire.stables.stables-list', [
            'stables' => $stables,
        ]);
    }
}
