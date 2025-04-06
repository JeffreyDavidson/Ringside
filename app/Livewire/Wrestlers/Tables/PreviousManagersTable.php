<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\WrestlerManager;
use Illuminate\Database\Eloquent\Builder;

class PreviousManagersTable extends BasePreviousManagersTable
{
    protected string $databaseTableName = 'wrestlers_managers';

    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    /**
     * @return Builder<WrestlerManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new \Exception("You didn't specify a wrestler");
        }

        return WrestlerManager::query()
            ->where('wrestler_id', $this->wrestlerId)
            ->whereNotNull('left_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.manager_id as manager_id',
        ]);
    }
}
