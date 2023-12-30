<?php

declare(strict_types=1);

namespace App\Http\Livewire\TagTeams;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use App\Models\TagTeam;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $rows
 * @property-read Builder $rowsQuery
 */
class MatchList extends Component
{
    use WithPerPagePagination;

    /**
     * Undocumented variable.
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
            ->eventMatches();
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
        return view('livewire.tag-teams.matches.match-list', [
            'eventMatches' => $this->rows,
        ]);
    }
}
