<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PreviousManagersList extends Component
{
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

        $previousManagers = $query->paginate();

        return view('livewire.wrestlers.previous-managers.previous-managers-list', [
            'previousManagers' => $previousManagers,
        ]);
    }
}
