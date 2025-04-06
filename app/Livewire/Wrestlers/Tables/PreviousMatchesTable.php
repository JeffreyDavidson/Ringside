<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BaseMatchesTable;
use App\Models\EventMatch;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;

class PreviousMatchesTable extends BaseMatchesTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new \Exception("You didn't specify a wrestler");
        }

        $wrestler = Wrestler::find($this->wrestlerId);

        return EventMatch::query()
            ->with(['event', 'titles', 'competitors', 'result.winner', 'result.decision'])
            ->withWhereHas('competitors', function ($query) use ($wrestler) {
                $query->whereMorphedTo('competitor', $wrestler);
            });
    }
}
