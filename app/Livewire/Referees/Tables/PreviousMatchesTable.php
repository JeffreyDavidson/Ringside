<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Livewire\Base\Tables\BaseMatchesTable;
use App\Models\EventMatch;
use Illuminate\Database\Eloquent\Builder;

class PreviousMatchesTable extends BaseMatchesTable
{
    /**
     * Referee to use for component.
     */
    public ?int $refereeId;

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->refereeId)) {
            throw new \Exception("You didn't specify a referee");
        }

        return EventMatch::query()
            ->with(['event', 'titles', 'competitors', 'result.winner', 'result.decision'])
            ->whereHas('referees', function (Builder $query) {
                $query->whereIn('referee_id', [$this->refereeId]);
            });
    }
}
