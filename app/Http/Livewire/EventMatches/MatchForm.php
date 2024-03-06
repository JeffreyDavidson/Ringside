<?php

declare(strict_types=1);

namespace App\Http\Livewire\EventMatches;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MatchForm extends Component
{
    /**
     * Event that match will be attached to.
     */
    public Event $event;

    /**
     * Match for the event.
     */
    public EventMatch $match;

    /**
     * Match type to target for subview.
     */
    public int $matchTypeId;

    /**
     * View to render for each match type.
     */
    public View $subViewToUse;

    /**
     * Undocumented function
     */
    public function mount(Event $event, EventMatch $match): void
    {
        $this->event = $event;
        $this->match = $match;
    }

    /**
     * Run action hook when match type id is changed.
     */
    public function updatedMatchTypeId(): View
    {
        $matchTypeSlug = MatchType::findOrFail($this->matchTypeId)->slug;

        return $this->subViewToUse = view('matches.types.'.$matchTypeSlug);
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.event-matches.match-form', [
            'match' => $this->match,
            'matchTypes' => MatchType::pluck('name', 'id'),
            'referees' => Referee::query()->get()->pluck('full_name', 'id'),
            'titles' => Title::pluck('name', 'id'),
        ]);
    }
}
