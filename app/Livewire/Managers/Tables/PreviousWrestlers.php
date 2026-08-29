<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Livewire\Attributes\Locked;

/** @extends DataTableComponent<WrestlerManager> */
class PreviousWrestlers extends DataTableComponent
{
    use ShowTableTrait;

    #[Locked]
    public ?int $managerId;

    protected string $databaseTableName = 'wrestlers_managers';

    protected string $resourceName = 'wrestlers';

    /** @return ManagerAssignmentBuilder<WrestlerManager> */
    public function builder(): ManagerAssignmentBuilder
    {
        $managerId = $this->requireContextId($this->managerId ?? null, 'manager');

        return WrestlerManager::query()
            ->forManagerId($managerId)
            ->forHistory()
            ->with('wrestler');
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.wrestler_id as wrestler_id',
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'wrestler.name'),
            DateColumn::make(__('wrestlers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('wrestlers.date_left'), 'fired_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
