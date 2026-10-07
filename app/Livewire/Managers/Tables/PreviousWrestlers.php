<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Base\Tables\BasePreviousManagedTable;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Livewire\Attributes\Locked;

/** @extends BasePreviousManagedTable<WrestlerManager> */
class PreviousWrestlers extends BasePreviousManagedTable
{
    #[Locked]
    public ?int $managerId = null;

    #[\Override]
    protected string $databaseTableName = 'wrestlers_managers';

    #[\Override]
    protected string $resourceName = 'wrestlers';

    #[\Override]
    protected string $managedRelation = 'wrestler';

    #[\Override]
    protected string $managedLabelGroup = 'wrestlers';

    #[\Override]
    protected string $hiredLabelKey = 'wrestlers.date_hired';

    #[\Override]
    protected string $firedLabelKey = 'wrestlers.date_left';

    /** @return ManagerAssignmentBuilder<WrestlerManager> */
    public function builder(): ManagerAssignmentBuilder
    {
        $managerId = $this->requireContextId($this->managerId ?? null, 'manager');

        return WrestlerManager::query()
            ->with('wrestler')
            ->whereHas('wrestler')
            ->forManagerId($managerId)
            ->forHistory();
    }

    protected function configure(): void
    {
        $this->authorizeContextRecord(Manager::class, $this->managerId, 'manager');

        $this->addAdditionalSelects([
            'wrestlers_managers.wrestler_id as wrestler_id',
        ]);
    }
}
