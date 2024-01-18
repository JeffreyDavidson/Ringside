<?php

declare(strict_types=1);

namespace App\Http\Livewire\Stables;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Stable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousManagersList extends Component
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
            ->previousManagers()
            ->addSelect(
                DB::raw("CONCAT(managers.first_name,' ', managers.last_name) AS full_name"),
            );

        $query = $this->applySorting($query);

        $previousManagers = $query->paginate();

        return view('livewire.stables.previous-managers.previous-managers-list', [
            'previousManagers' => $previousManagers,
        ]);
    }
}
