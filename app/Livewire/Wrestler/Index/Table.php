<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index;

use App\Builders\WrestlerBuilder;
use App\Livewire\Wrestler\Index\Traits\Sortable;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;
    use Sortable;

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
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = Wrestler::query()
            ->when(
                $this->filters['search'],
                function (WrestlerBuilder $query, string $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                }
            )
            ->orderBy('name');

        $query = $this->applySorting($query);

        $wrestlers = $query->paginate();

        return view('livewire.wrestler.index.table', [
            'wrestlers' => $wrestlers,
        ]);
    }
}
