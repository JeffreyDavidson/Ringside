<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Models\Stable;
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

    /**
     * @return array<int, Column>
     **/
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'wrestler_name'),
            Column::make(__('stables.date_joined'), 'date_joined'),
            Column::make(__('stables.date_left'), 'date_left'),
        ];
    }
}
