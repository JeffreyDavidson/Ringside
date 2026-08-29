<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Builders\Roster\ManagerBuilder;
use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Livewire\Table\Column;
use App\Models\Roster\Managers\Manager;
use App\Queries\Roster\StableManagerHistoryQuery;
use Livewire\Attributes\Locked;

/** @extends BasePreviousManagersTable<Manager> */
class PreviousManagers extends BasePreviousManagersTable
{
    public string $databaseTableName = 'managers';

    #[Locked]
    public ?int $stableId;

    /**
     * @return ManagerBuilder<Manager>
     */
    public function builder(): ManagerBuilder
    {
        $stableId = $this->requireContextId($this->stableId ?? null, 'stable');

        return StableManagerHistoryQuery::previousManagersForStableId($stableId);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('managers.name'), 'full_name')
                ->searchable(function (ManagerBuilder $builder, string $searchTerm): void {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('managers.status'), 'status')
                ->label(fn (Manager $manager) => $manager->status->label()),
        ];
    }
}
