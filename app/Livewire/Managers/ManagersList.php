<?php

declare(strict_types=1);

namespace App\Livewire\Managers;

use App\Builders\ManagerBuilder;
use App\Livewire\Datatable\WithSorting;
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
    public array $selectedManagerIds = [];

    /**
     * @var array<int>
     */
    public array $managerIdsOnPage = [];

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

        $this->managerIdsOnPage = $managers->map(fn (Manager $manager) => (string) $manager->id)->toArray();

        return view('livewire.managers.managers-list', [
            'managers' => $managers,
        ]);
    }
}
