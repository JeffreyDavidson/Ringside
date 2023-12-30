<?php

declare(strict_types=1);

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\TagTeam;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $rows
 * @property-read Builder $rowsQuery
 */
class TitleChampionshipsList extends Component
{
    use WithPerPagePagination;
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
     * Undocumented function.
     */
    #[Computed]
    public function rowsQuery(): Builder
    {
        $query = $this->tagTeam
            ->titleChampionships()
            ->join('titles', 'titles.id', '=', 'title_championships.title_id')
            ->addSelect(
                'title_championships.title_id',
                DB::raw('count(title_id) as title_count'),
                DB::raw('max(won_at) as won_at'),
                DB::raw('case when MAX(lost_at IS NULL) = 0 THEN max(lost_at) END AS lost_at')
            )
            ->groupBy('title_id');

        return $this->applySorting($query);
    }

    /**
     * Undocumented function.
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->applyPagination($this->rowsQuery);
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.tag-teams.title-championships.title-championships-list', [
            'titlesChampionships' => $this->rows,
        ]);
    }
}
