<?php

declare(strict_types=1);

namespace App\Http\Livewire\Stables;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Stable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousWrestlersList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Stable to use for component.
     */
    public Stable $stable;

    /**
     * Set the Stable to be used for this component.
     */
    public function mount(Stable $stable): void
    {
        $this->stable = $stable;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->stable
            ->previousWrestlers();

        $query = $this->applySorting($query);

        $previousWrestlers = $query->paginate();

        return view('livewire.stables.previous-wrestlers.previous-wrestlers-list', [
            'previousWrestlers' => $previousWrestlers,
        ]);
    }
}
