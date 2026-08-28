<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends BasePreviousManagersTable<WrestlerManager> */
class PreviousManagers extends BasePreviousManagersTable
{
    /**
     * Wrestler to use for component.
     */
    #[Locked]
    public ?int $wrestlerId;

    public string $databaseTableName = 'wrestlers_managers';

    /** @return ManagerAssignmentBuilder<WrestlerManager> */
    public function builder(): ManagerAssignmentBuilder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        return WrestlerManager::query()
            ->with('manager')
            ->whereHas('manager')
            ->forWrestlerId($this->wrestlerId)
            ->forHistory();
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.manager_id',
        ]);
    }
}
