<?php

declare(strict_types=1);

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Wrestler;
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
    use WithSorting;

    /**
     * Undocumented variable.
     */
    public Wrestler $wrestler;

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
    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    /**
     * Undocumented function.
     */
    #[Computed]
    public function rowsQuery(): Builder
    {
        $query = $this->wrestler
            ->eventMatches()
            ->join('events', 'events.id', '=', 'event_matches.event_id');

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
        return view('livewire.wrestlers.matches.match-list', [
            'eventMatches' => $this->rows,
        ]);
    }
}
