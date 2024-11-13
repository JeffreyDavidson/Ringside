<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Builders\EventBuilder;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class EventsList extends Component
{
    use WithPagination;

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
    public array $selectedEventIds = [];

    /**
     * @var array<int>
     */
    public array $eventIdsOnPage = [];

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = Event::query()
            ->when(
                $this->filters['search'],
                function (EventBuilder $query, string $search) {
                    $query->where('name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('name');

        $events = $query->paginate();

        $this->eventIdsOnPage = $events->map(fn (Event $event) => (string) $event->id)->toArray();

        return view('livewire.events.events-list', [
            'events' => $events,
        ]);
    }
}
