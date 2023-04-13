<?php

declare(strict_types=1);

namespace App\Http\Livewire\EventMatches;

use App\Http\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use Illuminate\View\View;

class MatchForm extends BaseComponent
{
    /**
     * Event that match will be attaached to.
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
     * View to rendor for each match type.
     */
    public $subViewToUse;

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
    public function updatedMatchTypeId(): string
    {
        $matchTypeSlug = MatchType::findOrFail($this->matchTypeId)->slug;

        return $this->subViewToUse = 'matches.types.'.$matchTypeSlug;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.matches.create', [
            'match' => $this->match,
            'matchTypes' => MatchType::pluck('name', 'id'),
            'referees' => Referee::query()->get()->pluck('full_name', 'id'),
            'titles' => Title::pluck('name', 'id'),
        ]);
    }
}
