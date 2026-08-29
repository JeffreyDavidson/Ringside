<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Roster\StableBuilder;
use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Roster\Stables\Stable;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends BasePreviousStablesTable<Stable> */
class PreviousStables extends BasePreviousStablesTable
{
    /**
     * Wrestler to use for component.
     */
    #[Locked]
    public ?int $wrestlerId;

    public string $databaseTableName = 'stables_wrestlers';

    /**
     * @return StableBuilder<Stable>
     */
    public function builder(): StableBuilder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        return Stable::query()
            ->previousForWrestlerId($this->wrestlerId);
    }

    protected function configure(): void
    {
        $this->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPerPageAccepted([5, 10, 25, 50, 100]);
    }
}
