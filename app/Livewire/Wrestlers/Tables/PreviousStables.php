<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Roster\StableBuilder;
use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

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
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        return Stable::query()
            ->previousForWrestlerId($wrestlerId);
    }

    protected function configure(): void
    {
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        Gate::authorize('view', Wrestler::query()->findOrFail($wrestlerId));

        $this->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPerPageAccepted([5, 10, 25, 50, 100]);
    }
}
