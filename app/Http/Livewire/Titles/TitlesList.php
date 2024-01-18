<?php

declare(strict_types=1);

namespace App\Http\Livewire\Titles;

use App\Builders\TitleBuilder;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TitlesList extends Component
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
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = Title::query()
            ->when(
                $this->filters['search'],
                function (TitleBuilder $query, string $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('name');

        $query = $this->applySorting($query);

        $titles = $query->paginate();

        return view('livewire.titles.titles-list', [
            'titles' => $titles,
        ]);
    }
}
