<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use LogicException;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Wrestler to use for component.
     */
    #[Locked]
    public ?int $wrestlerId;

    public string $databaseTableName = 'events_matches_competitors';

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        $wrestler = Wrestler::query()->findOrFail($this->wrestlerId);

        return EventMatch::query()
            ->forPastEvents()
            ->with(['titles', 'competitors.competitor', 'competitors.side', 'winningSide.competitors.competitor'])
            ->forCompetitor($wrestler)
            ->latestEventFirst();
    }
}
