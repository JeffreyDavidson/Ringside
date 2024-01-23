<?php

declare(strict_types=1);

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PreviousManagersList extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Wrestler to use for component.
     */
    public Wrestler $wrestler;

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
            ->previousManagers()
            ->addSelect(
                DB::raw("CONCAT(managers.first_name,' ', managers.last_name) AS full_name"),
            );

        $query = $this->applySorting($query);

        $previousManagers = $query->paginate();

        return view('livewire.wrestlers.previous-managers.previous-managers-list', [
            'previousManagers' => $previousManagers,
        ]);
    }
}
