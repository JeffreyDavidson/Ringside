<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousMatchesList extends Component
{
    use WithPagination;

    /**
     * Tag Team to use for component.
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
     * Set the Tag Team to be used for this component.
     */
    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeam = $tagTeam;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->tagTeam
            ->previousMatches();

        $previousMatches = $query->paginate();

        return view('livewire.tag-teams.previous-matches.previous-matches-list', [
            'previousMatches' => $previousMatches,
        ]);
    }
}
