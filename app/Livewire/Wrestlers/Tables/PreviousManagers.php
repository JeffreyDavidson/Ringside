<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @return Builder<WrestlerManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        return WrestlerManager::query()
            ->with('manager')
            ->whereHas('manager') // Only include records where manager exists (not soft deleted)
            ->where('wrestler_id', $this->wrestlerId)
            ->ended()
            ->mostRecentlyHiredFirst();
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.manager_id',
        ]);
    }
}
