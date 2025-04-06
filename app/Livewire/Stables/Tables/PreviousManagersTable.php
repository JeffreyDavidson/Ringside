<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\StableManager;
use Illuminate\Database\Eloquent\Builder;

class PreviousManagersTable extends BasePreviousManagersTable
{
    protected string $databaseTableName = 'stables_managers';

    public ?int $stableId;

    /**
     * @return Builder<StableManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new \Exception("You didn't specify a stable");
        }

        return StableManager::query()
            ->with(['manager'])
            ->where('stable_id', $this->stableId)
            ->whereNotNull('left_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_managers.manager_id as manager_id',
        ]);
    }
}
