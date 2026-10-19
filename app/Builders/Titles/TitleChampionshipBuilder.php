<?php

declare(strict_types=1);

namespace App\Builders\Titles;

use App\Builders\Concerns\OrdersByKeyForLocking;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\TitleChampionship;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TModel of TitleChampionship
 *
 * @extends Builder<TModel>
 */
class TitleChampionshipBuilder extends Builder
{
    use OrdersByKeyForLocking;

    /**
     * Reigns that were won or lost at one of the given matches.
     *
     * @param  array<int, int>|Builder<*>|Relation<*, *, *>  $matchIds
     */
    public function wonOrLostAtMatches(array|Builder|Relation $matchIds): static
    {
        $this->where(fn (Builder $query): Builder => $query
            ->whereIn('won_match_id', $matchIds)
            ->orWhereIn('lost_match_id', $matchIds));

        return $this;
    }

    public function forTitleId(int $titleId): static
    {
        $this->where('title_id', $titleId);

        return $this;
    }

    public function current(): static
    {
        $this->whereNull('lost_at');

        return $this;
    }

    /**
     * The reign that held the title at the given moment (`won_at <= date`, and not lost or lost after it).
     * Without a date (an unscheduled event) it is the current reign.
     */
    public function heldOn(?DateTimeInterface $date): static
    {
        if (! $date instanceof DateTimeInterface) {
            return $this->current();
        }

        return $this
            ->where('won_at', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('lost_at')
                    ->orWhere('lost_at', '>', $date);
            });
    }

    public function previous(): static
    {
        $this->whereNotNull('lost_at');

        return $this;
    }

    public function forChampion(Wrestler|TagTeam $champion): static
    {
        return $this->whereMorphedTo('champion', $champion);
    }

    public function forWrestlerId(int $wrestlerId): static
    {
        return $this->forChampionId($wrestlerId, Wrestler::class);
    }

    public function forTagTeamId(int $tagTeamId): static
    {
        return $this->forChampionId($tagTeamId, TagTeam::class);
    }

    /**
     * @param  class-string<TagTeam|Wrestler>  $championType
     */
    private function forChampionId(int $championId, string $championType): static
    {
        $this->whereHasMorph(
            'champion',
            $championType,
            function (Builder $query) use ($championId): void {
                $query->whereKey($championId);
            },
        );

        return $this;
    }

    public function forPreviousHistory(): static
    {
        return $this
            ->previous()
            ->mostRecentlyLostFirst()
            ->withPreviousChampionshipId()
            ->with(['title.promotion', 'previousChampionship.champion']);
    }

    public function mostRecentlyWonFirst(): static
    {
        $this->orderByDesc('won_at');

        return $this;
    }

    public function mostRecentlyLostFirst(): static
    {
        $this->orderByDesc('lost_at');

        return $this;
    }

    public function withPreviousChampionshipId(): static
    {
        $this->addSelect([
            'previous_championship_id' => TitleChampionship::query()
                ->from('titles_championships as previous_championships')
                ->select('previous_championships.id')
                ->whereColumn('previous_championships.title_id', 'titles_championships.title_id')
                ->whereColumn('previous_championships.won_at', '<', 'titles_championships.won_at')
                ->orderByDesc('previous_championships.won_at')
                ->limit(1),
        ]);

        return $this;
    }
}
