<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
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

        return EventMatch::query()
            ->with(['titles', 'competitors', 'result.winner', 'result.decision'])
            ->withWhereHas('referees', function (Builder $query): void {
                $query->whereIn('referee_id', [$this->refereeId]);
            })
            ->withWhereHas('event', function (Builder $query): void {
                $query->whereNotNull('date')->where('date', '<', now()->toDateString());
            })
            ->orderByDesc('date');
    }
}
