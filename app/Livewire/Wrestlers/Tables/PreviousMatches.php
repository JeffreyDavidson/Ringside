<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

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
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        return EventMatch::query()
            ->forHistory()
            ->forWrestlerId($wrestlerId);
    }

    protected function configure(): void
    {
        parent::configure();

        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        Gate::authorize('view', Wrestler::query()->findOrFail($wrestlerId));
    }
}
