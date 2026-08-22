<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use Livewire\Attributes\Locked;
use LogicException;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Wrestler to use for component.
     */
    #[Locked]
    public ?int $wrestlerId;

    /** @return EventMatchBuilder<EventMatch> */
    public function builder(): EventMatchBuilder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        $wrestler = Wrestler::query()->findOrFail($this->wrestlerId);

        return EventMatch::query()
            ->forHistory()
            ->forCompetitor($wrestler);
    }
}
