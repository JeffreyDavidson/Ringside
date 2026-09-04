<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Builders\Roster\StableBuilder;
use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Livewire\Table\Column;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Queries\Roster\StableManagerHistoryQuery;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

/** @extends BasePreviousStablesTable<Stable> */
class PreviousStables extends BasePreviousStablesTable
{
    /**
     * ManagerId to use for component.
     */
    #[Locked]
    public ?int $managerId;

    protected string $databaseTableName = 'stables';

    protected string $resourceName = 'stables';

    /**
     * Get stables that the manager was associated with through wrestlers/tag teams they managed.
     *
     * @return StableBuilder<Stable>
     */
    public function builder(): StableBuilder
    {
        $managerId = $this->requireContextId($this->managerId ?? null, 'manager');

        return StableManagerHistoryQuery::previousStablesForManagerId($managerId);
    }

    public function columns(): array
    {
        return [
            Column::make(__('stables.name'), 'name')
                ->searchable(),
        ];
    }

    protected function configure(): void
    {
        $managerId = $this->requireContextId($this->managerId ?? null, 'manager');

        Gate::authorize('view', Manager::query()->findOrFail($managerId));
    }
}
