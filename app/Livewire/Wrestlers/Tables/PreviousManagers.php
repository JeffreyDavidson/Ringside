<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\Wrestlers\WrestlerManager;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousManagers extends BasePreviousManagersTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    public string $databaseTableName = 'wrestlers_managers';

    /**
     * @return Builder<WrestlerManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new Exception("You didn't specify a wrestler");
        }

        return WrestlerManager::query()
            ->where('wrestler_id', $this->wrestlerId)
            ->whereNotNull('fired_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.manager_id',
        ]);
    }
}
