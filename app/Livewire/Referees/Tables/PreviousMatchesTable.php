<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Enums\EventStatus;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\EventMatch;
use Illuminate\Database\Eloquent\Builder;

class PreviousMatchesTable extends BasePreviousMatchesTable
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
            ->with(['titles', 'competitors', 'result.winner', 'result.decision'])
            ->whereHas('referees', function (Builder $query) {
                $query->whereIn('referee_id', [$this->refereeId]);
            })
            ->withWhereHas('event', function ($query) {
                $query->where('status', EventStatus::Past);
            });
    }
}
