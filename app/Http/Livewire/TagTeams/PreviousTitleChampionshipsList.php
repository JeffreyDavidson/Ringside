<?php

declare(strict_types=1);

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousTitleChampionshipsList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Tag team to use for component.
     */
    public TagTeam $tagTeam;

    /**
     * List of filters that are allowed.
     *
     * @var array<string, string>
     */
    public array $filters = [
        'search' => '',
    ];

    /**
     * Undocumented function.
     */
    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeam = $tagTeam;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->tagTeam
            ->previousTitleChampionships()
            ->with('title')
            ->addSelect(
                'title_championships.title_id',
                'title_championships.won_at',
                'title_championships.lost_at',
                DB::raw('DATEDIFF(COALESCE(lost_at, NOW()), won_at) AS days_held_count')
            );

        $query = $this->applySorting($query);

        $previousTitleChampionships = $query->paginate();

        return view('livewire.tag-teams.previous-title-championships.previous-title-championships-list', [
            'previousTitleChampionships' => $previousTitleChampionships,
        ]);
    }
}
