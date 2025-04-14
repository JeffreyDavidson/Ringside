<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Enums\EventStatus;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\EventMatch;
use App\Models\Wrestler;
use Exception;
use Illuminate\Database\Eloquent\Builder;

final class PreviousMatchesTable extends BasePreviousMatchesTable
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
            throw new Exception("You didn't sp ecify a wrestler");
        }

        $wrestler = Wrestler::find($this->wrestlerId);

        return EventMatch::query()
            ->with(['titles', 'result.winner', 'result.decision'])
            ->withWhereHas('competitors', function ($query) use ($wrestler) {
                $query->whereMorphedTo('competitor', $wrestler);
            })
            ->withWhereHas('event', function ($query) {
                $query->whereNotNull('date')->where('status', EventStatus::Past);
            })
            ->orderByDesc('date');
    }
}
