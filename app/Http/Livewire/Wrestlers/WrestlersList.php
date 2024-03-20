<?php

declare(strict_types=1);

namespace App\Http\Livewire\Wrestlers;

use App\Builders\WrestlerBuilder;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class WrestlersList extends Component
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
    public array $selectedWrestlerIds = [];

    /**
     * @var array<int>
     */
    public array $wrestlerIdsOnPage = [];

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

        $this->wrestlerIdsOnPage = $wrestlers->map(fn (Wrestler $wrestler) => (string) $wrestler->id)->toArray();

        return view('livewire.wrestlers.wrestlers-list', [
            'wrestlers' => $wrestlers,
        ]);
    }
}
