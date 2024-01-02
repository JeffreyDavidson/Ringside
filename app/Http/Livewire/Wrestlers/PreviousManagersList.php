<?php

declare(strict_types=1);

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use App\Http\Livewire\Datatable\WithSorting;
use App\Models\Wrestler;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $rows
 * @property-read Builder $rowsQuery
 */
class PreviousManagersList extends Component
{
    use WithPerPagePagination;
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
     * Run the query for this component.
     */
    #[Computed]
    public function rowsQuery(): Builder
    {
        $query = $this->wrestler
            ->previousManagers()
            ->addSelect(
                DB::raw("CONCAT(managers.first_name,' ', managers.last_name) AS full_name"),
            );

        return $this->applySorting($query);
    }

    /**
     * Apply pagination to the component query results.
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->applyPagination($this->rowsQuery);
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        return view('livewire.wrestlers.previous-managers.previous-managers-list', [
            'previousManagers' => $this->rows,
        ]);
    }
}
