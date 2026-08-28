<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Support\ConsecutiveIntegerSequence;
use Illuminate\Database\Eloquent\Collection;

final class MatchEntryOrderRequirement
{
    public function __construct(private readonly ConsecutiveIntegerSequence $sequence) {}

    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     */
    public function ensureSatisfied(EventMatch $match, Collection $competitors): void
    {
        if ($match->match_type !== MatchType::RoyalRumble) {
            return;
        }

        $entryOrders = $competitors
            ->map(fn (MatchCompetitor $competitor): ?int => $competitor->entry_order)
            ->all();

        if (! $this->sequence->isValid($entryOrders)) {
            throw InvalidMatchOutcomeException::invalidEntryOrder();
        }
    }
}
