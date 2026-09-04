<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

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
        $refereeId = $this->requireContextId($this->refereeId ?? null, 'referee');

        return EventMatch::query()
            ->forHistory()
            ->forRefereeId($refereeId);
    }

    protected function configure(): void
    {
        $refereeId = $this->requireContextId($this->refereeId ?? null, 'referee');

        Gate::authorize('view', Referee::query()->findOrFail($refereeId));
    }
}
