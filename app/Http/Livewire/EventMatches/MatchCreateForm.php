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

class MatchCreateForm extends BaseComponent
{
    public Event $event;

    public EventMatch $match;

    /**
     * Subview to load competitors.
     *
     * @var int
     */
    public int $matchTypeId = 0;

    public $subviewToUse;

    public function mount(EventMatch $match): void
    {
        $this->event = request()->route()->parameter('event');
        $this->match = $match;
        $this->subViewToUse = '';
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
