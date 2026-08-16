<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Referee to use for component.
     */
    public ?int $refereeId;

    protected string $databaseTableName = 'event_matches';

    protected string $resourceName = 'matches';

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->refereeId)) {
            throw new LogicException('A referee was not provided.');
        }

        $referee = Referee::query()->findOrFail($this->refereeId);

        return EventMatch::query()
            ->forPastEvents()
            ->with(['titles', 'competitors.competitor', 'competitors.side', 'winningSide.competitors.competitor'])
            ->forReferee($referee)
            ->latestEventFirst();
    }
}
