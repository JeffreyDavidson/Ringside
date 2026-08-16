<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @extends BasePreviousStablesTable<Stable> */
class PreviousStables extends BasePreviousStablesTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    public string $databaseTableName = 'stables_wrestlers';

    /**
     * @return Builder<Stable>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        return Stable::query()
            ->previousForWrestlerId($this->wrestlerId);
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPaginationEnabled()
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setLoadingPlaceholderContent('Loading')
            ->setLoadingPlaceholderEnabled();
    }
}
