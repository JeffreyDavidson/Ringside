<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Wrestler to use for component.
     */
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

        $wrestler = Wrestler::find($this->wrestlerId);

        return EventMatch::query()
            ->with(['titles', 'result.winner', 'result.decision'])
            ->withWhereHas('competitors', function (Builder $query) use ($wrestler): void {
                $query->whereMorphedTo('competitor', $wrestler);
            })
            ->withWhereHas('event', function (Builder $query): void {
                $query->whereNotNull('date')->where('date', '<', now()->toDateString());
            })
            ->orderByDesc('date');
    }
}
