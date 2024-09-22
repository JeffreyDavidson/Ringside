<?php

declare(strict_types=1);

namespace App\Livewire\Events\Matches;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MatchesList extends Component
{
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
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->event
            ->matches();

        $matches = $query->get();

        return view('livewire.events.matches.matches-list', [
            'matches' => $matches,
        ]);
    }
}
