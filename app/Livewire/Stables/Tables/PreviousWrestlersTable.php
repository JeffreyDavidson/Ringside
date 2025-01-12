<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Models\Stable;
use Illuminate\Contracts\View\View;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousWrestlersTable extends DataTableComponent
{
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

    public function configure(): void {}

    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'wrestler_name'),
            Column::make(__('stables.date_joined'), 'date_joined'),
            Column::make(__('stables.date_left'), 'date_left'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->stable
            ->previousWrestlers();

        $previousWrestlers = $query->paginate();

        return view('livewire.stables.previous-wrestlers.previous-wrestlers-list', [
            'previousWrestlers' => $previousWrestlers,
        ]);
    }
}
