<?php

declare(strict_types=1);

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\BaseComponent;
use App\Models\TagTeam;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

/**
 * @property-read LengthAwarePaginator $rows
 * @property-read Builder $rowsQuery
 */
class TitleChampionshipsList extends BaseComponent
{
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
        return $this->tagTeam
            ->titleChampionships()
            ->with('title')
            ->addSelect(
                'title_championships.title_id',
                DB::raw('count(title_id) as title_count'),
                DB::raw('max(won_at) as last_held_reign')
            )
            ->groupBy('title_id');
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