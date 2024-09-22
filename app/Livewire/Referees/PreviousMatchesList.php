<?php

declare(strict_types=1);

namespace App\Livewire\Referees;

use App\Livewire\Datatable\WithSorting;
use App\Models\Referee;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousMatchesList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Referee to use for component.
     */
    public Referee $referee;

    /**
     * List of filters that are allowed.
     *
     * @var array<string, string>
     */
    public array $filters = [
        'search' => '',
    ];

    /**
     * Set the Referee to be used for this component.
     */
    public function mount(Referee $referee): void
    {
        $this->referee = $referee;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->referee
            ->previousMatches();

        $query = $this->applySorting($query);

        $previousMatches = $query->paginate();

        return view('livewire.referees.previous-matches.previous-matches-list', [
            'previousMatches' => $previousMatches,
        ]);
    }
}
