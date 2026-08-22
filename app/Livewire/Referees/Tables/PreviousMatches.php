<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use Livewire\Attributes\Locked;
use LogicException;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Referee to use for component.
     */
    #[Locked]
    public ?int $refereeId;

    /** @return EventMatchBuilder<EventMatch> */
    public function builder(): EventMatchBuilder
    {
        if (! isset($this->refereeId)) {
            throw new LogicException('A referee was not provided.');
        }

        $referee = Referee::query()->findOrFail($this->refereeId);

        return EventMatch::query()
            ->forHistory()
            ->forReferee($referee);
    }
}
