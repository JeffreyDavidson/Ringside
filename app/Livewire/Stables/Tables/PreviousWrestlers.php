<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Builders\Roster\StableMembershipBuilder;
use App\Livewire\Base\Tables\BasePreviousMembersTable;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableWrestler;
use Livewire\Attributes\Locked;

/** @extends BasePreviousMembersTable<StableWrestler> */
class PreviousWrestlers extends BasePreviousMembersTable
{
    #[\Override]
    protected string $resourceName = 'wrestlers';

    #[\Override]
    protected string $databaseTableName = 'stables_wrestlers';

    #[\Override]
    protected string $memberRelation = 'wrestler';

    #[\Override]
    protected string $memberLabelGroup = 'wrestlers';

    #[\Override]
    protected string $dateLabelGroup = 'stables';

    #[Locked]
    public ?int $stableId = null;

    /** @return StableMembershipBuilder<StableWrestler> */
    public function builder(): StableMembershipBuilder
    {
        $stableId = $this->requireContextId($this->stableId ?? null, 'stable');

        return StableWrestler::query()
            ->with('wrestler')
            ->forStableId($stableId)
            ->forHistory();
    }

    protected function configure(): void
    {
        $this->authorizeContextRecord(Stable::class, $this->stableId, 'stable');

        $this->addAdditionalSelects([
            'stables_wrestlers.wrestler_id',
            'stables_wrestlers.stable_id',
            'stables_wrestlers.joined_at',
            'stables_wrestlers.left_at',
        ]);
    }
}
