<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/** @extends Collection<int, TitleChampionship> */
class TitleChampionshipCollection extends Collection
{
    /**
     * The reigns of one title that are not soft deleted. A reign deleted after the collection was loaded drops out
     * the next time this is called, so the active check is never cached.
     */
    public function forTitle(Title $title): static
    {
        return $this
            ->where('title_id', $title->id)
            ->filter(fn (TitleChampionship $reign): bool => $reign->deleted_at === null);
    }

    /** The reign the given match started. */
    public function wonAt(EventMatch $match): ?TitleChampionship
    {
        return $this->firstWhere('won_match_id', $match->id);
    }

    /** The reign the given match ended. */
    public function lostAt(EventMatch $match): ?TitleChampionship
    {
        return $this->firstWhere('lost_match_id', $match->id);
    }

    /** The most recently won reign that has not ended. */
    public function current(): ?TitleChampionship
    {
        return $this->whereNull('lost_at')->sortByDesc('won_at')->first();
    }

    /** The reigns won after the date, leaving out the reign the given match started. */
    public function laterThan(Carbon $date, EventMatch $except): static
    {
        return $this->filter(
            fn (TitleChampionship $reign): bool => $reign->won_match_id !== $except->id
                && $reign->won_at->greaterThan($date),
        );
    }

    /**
     * The reign that was already held when the date arrived: it began strictly before the date and had not ended
     * by then. A reign the given match started is ignored, and one the given match ended still counts although it
     * ended on the date. Differs from covering() on purpose.
     */
    public function heldBefore(Carbon $date, EventMatch $match): ?TitleChampionship
    {
        return $this->first(
            fn (TitleChampionship $reign): bool => $reign->won_match_id !== $match->id
                && $reign->won_at->lessThan($date)
                && ($reign->lost_at === null || $reign->lost_at->greaterThan($date) || $reign->lost_match_id === $match->id),
        );
    }

    /**
     * The reign that held the title at the date: it began on or before the date and had not ended by then. Unlike
     * heldBefore(), a reign that began on the date counts and no match is exempted.
     */
    public function covering(Carbon $date): ?TitleChampionship
    {
        return $this->first(
            fn (TitleChampionship $reign): bool => $reign->won_at->lessThanOrEqualTo($date)
                && ($reign->lost_at === null || $reign->lost_at->greaterThan($date)),
        );
    }
}
