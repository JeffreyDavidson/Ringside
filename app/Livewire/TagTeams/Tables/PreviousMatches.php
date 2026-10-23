<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Livewire\Concerns\FiltersMatchHistory;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use Livewire\Attributes\Locked;
use Override;

class PreviousMatches extends BasePreviousMatchesTable
{
    use FiltersMatchHistory;

    /**
     * Tag Team to use for component.
     */
    #[Locked]
    public ?int $tagTeamId = null;

    /** @return EventMatchBuilder<EventMatch> */
    public function builder(): EventMatchBuilder
    {
        return $this->historyForSubject();
    }

    protected function historySubject(): TagTeam
    {
        return TagTeam::query()->findOrFail($this->requireContextId($this->tagTeamId, 'tag team'));
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->configureHistory();

        $this->authorizeContextRecord(TagTeam::class, $this->tagTeamId, 'tag team');
    }
}
