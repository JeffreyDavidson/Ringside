<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use Livewire\Attributes\Locked;
use LogicException;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Tag Team to use for component.
     */
    #[Locked]
    public ?int $tagTeamId;

    /** @return EventMatchBuilder<EventMatch> */
    public function builder(): EventMatchBuilder
    {
        if (! isset($this->tagTeamId)) {
            throw new LogicException('A tag team was not provided.');
        }

        return EventMatch::query()
            ->forHistory()
            ->forTagTeamId($this->tagTeamId);
    }
}
