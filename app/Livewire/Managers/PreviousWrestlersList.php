<?php

declare(strict_types=1);

namespace App\Livewire\Managers;

use App\Livewire\Datatable\WithSorting;
use App\Models\Manager;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousWrestlersList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Manager to use for component.
     */
    public Manager $manager;

    /**
     * Set the Manager to be used for this component.
     */
    public function mount(Manager $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->manager
            ->previousWrestlers();

        $query = $this->applySorting($query);

        $previousWrestlers = $query->paginate();

        return view('livewire.managers.previous-wrestlers.previous-wrestlers-list', [
            'previousWrestlers' => $previousWrestlers,
        ]);
    }
}
