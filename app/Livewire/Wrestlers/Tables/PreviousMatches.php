<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Livewire\Concerns\FiltersMatchHistory;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use Livewire\Attributes\Locked;
use Override;

class PreviousMatches extends BasePreviousMatchesTable
{
    use FiltersMatchHistory;

    /**
     * Wrestler to use for component.
     */
    #[Locked]
    public ?int $wrestlerId = null;

    /** @return EventMatchBuilder<EventMatch> */
    public function builder(): EventMatchBuilder
    {
        return $this->historyForSubject();
    }

    protected function historySubject(): Wrestler
    {
        return Wrestler::query()->findOrFail($this->requireContextId($this->wrestlerId, 'wrestler'));
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->configureHistory();

        $this->authorizeContextRecord(Wrestler::class, $this->wrestlerId, 'wrestler');
    }
}
