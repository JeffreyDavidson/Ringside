<?php

declare(strict_types=1);

namespace App\Http\Livewire\TagTeams;

use App\Builders\TagTeamBuilder;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TagTeamsList extends Component
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
        $query = TagTeam::query()
            ->when(
                $this->filters['search'],
                function (TagTeamBuilder $query, string $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('name');

        $query = $this->applySorting($query);

        $tagTeams = $query->paginate();

        return view('livewire.tag-teams.tag-teams-list', [
            'tagTeams' => $tagTeams,
        ]);
    }
}
