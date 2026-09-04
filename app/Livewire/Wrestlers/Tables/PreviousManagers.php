<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

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
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        return WrestlerManager::query()
            ->with('manager')
            ->whereHas('manager')
            ->forWrestlerId($wrestlerId)
            ->forHistory();
    }

    protected function configure(): void
    {
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        Gate::authorize('view', Wrestler::query()->findOrFail($wrestlerId));

        $this->addAdditionalSelects([
            'wrestlers_managers.manager_id',
        ]);
    }
}
