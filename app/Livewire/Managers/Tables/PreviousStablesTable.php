<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\StableManager;
use Illuminate\Database\Eloquent\Builder;

class PreviousStablesTable extends BasePreviousStablesTable
{
    protected string $databaseTableName = 'stables_managers';

    /**
     * ManagerId to use for component.
     */
    public ?int $managerId;

    /**
     * @return Builder<StableManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->managerId)) {
            throw new \Exception("You didn't specify a manager");
        }

        return StableManager::query()
            ->where('manager_id', $this->managerId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_managers.stable_id as stable_id',
        ]);
    }
}
