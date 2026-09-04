<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Roster\StableBuilder;
use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

/** @extends BasePreviousStablesTable<Stable> */
class PreviousStables extends BasePreviousStablesTable
{
    protected string $databaseTableName = 'stables';

    #[Locked]
    public ?int $tagTeamId;

    /**
     * @return StableBuilder<Stable>
     */
    public function builder(): StableBuilder
    {
        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        return Stable::query()
            ->previousForTagTeamId($tagTeamId);
    }

    protected function configure(): void
    {
        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        Gate::authorize('view', TagTeam::query()->findOrFail($tagTeamId));

        $this->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPerPageAccepted([5, 10, 25, 50, 100]);
    }
}
