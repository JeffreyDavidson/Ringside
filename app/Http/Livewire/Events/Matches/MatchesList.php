<?php

declare(strict_types=1);

namespace App\Http\Livewire\Events\Matches;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Event;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $rows
 * @property-read Builder $rowsQuery
 */
class MatchesList extends Component
{
    use WithPerPagePagination;
    use WithSorting;

    /**
     * Event to use for component.
     */
    public Event $event;

    /**
     * Set the Event to be used for this component.
     */
    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    /**
     * Run the query for this component.
     */
    #[Computed]
    public function rowsQuery(): Builder
    {
        return $this->event
            ->matches();
    }

    /**
     * Apply pagination to the component query results.
     */
    #[Computed]
    public function rows(): Collection
    {
        return $this->rowsQuery->get();
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.events.matches.matches-list', [
            'matches' => $this->rows,
        ]);
    }
}
