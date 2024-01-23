<?php

declare(strict_types=1);

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousMatchesList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Wrestler to use for component.
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
     * Set the Wrestler to be used for this component.
     */
    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->wrestler
            ->previousMatches();

        $query = $this->applySorting($query);

        $previousMatches = $query->paginate();

        return view('livewire.wrestlers.previous-matches.previous-matches-list', [
            'previousMatches' => $previousMatches,
        ]);
    }
}
