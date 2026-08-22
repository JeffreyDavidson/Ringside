<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends BasePreviousStablesTable<Stable> */
class PreviousStables extends BasePreviousStablesTable
{
    protected string $databaseTableName = 'stables';

    #[Locked]
    public ?int $tagTeamId;

    /**
     * @return Builder<Stable>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new LogicException('A tag team was not provided.');
        }

        return Stable::query()
            ->previousForTagTeamId($this->tagTeamId);
    }

    protected function configure(): void
    {
        $this->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPerPageAccepted([5, 10, 25, 50, 100]);
    }
}
