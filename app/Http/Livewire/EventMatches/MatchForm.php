<?php

namespace App\Http\Livewire\EventMatches;

use App\Http\Livewire\BaseComponent;
use App\Models\EventMatch;
use App\Models\MatchType;

class MatchForm extends BaseComponent
{
    /**
     * Event match to be loaded.
     *
     * @var EventMatch
     */
    private EventMatch $match;

    /**
     * Subview to load competitors.
     *
     * @var string
     */
    private string $subViewToUse;

    /**
     * Subview to load competitors.
     *
     * @var int
     */
    private int $matchTypeId;

    /**
     * Apply the EventMatch to the Match form instance.
     *
     * @param  \App\Models\EventMatch $match
     *
     * @return void
     */
    public function mount(EventMatch $match)
    {
        $this->match = $match;
    }

    /**
     * Run action hook when match type id is changed.
     *
     * @return string
     */
    public function updatedMatchTypeId()
    {
        $matchTypeSlug = MatchType::find($this->matchTypeId)->slug;

        return $this->subViewToUse = 'matches.types.'.$matchTypeSlug;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.matches.form', [
            'match' => $this->match,
        ]);
    }
}
