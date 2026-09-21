<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

final class MatchCompetitorStateMapper
{
    /**
     * @param  Collection<int, MatchSide>  $sides
     * @return array<int, array{wrestlers: array<int>, tag_teams: array<int>}>
     */
    public function fromSides(Collection $sides, bool $usesIndividualSides): array
    {
        $competitorsBySide = $sides
            ->map(fn (MatchSide $side): array => $this->fromSide($side))
            ->values()
            ->all();

        if (! $usesIndividualSides) {
            return $competitorsBySide;
        }

        return [[
            'wrestlers' => collect($competitorsBySide)
                ->flatMap(fn (array $side): array => $side['wrestlers'])
                ->values()
                ->all(),
            'tag_teams' => [],
        ]];
    }

    /**
     * @return array{wrestlers: array<int>, tag_teams: array<int>}
     */
    private function fromSide(MatchSide $side): array
    {
        return [
            'wrestlers' => $side->competitors
                ->wrestlers()
                ->map(fn (Wrestler $wrestler): int => $wrestler->id)
                ->values()
                ->all(),
            'tag_teams' => $side->competitors
                ->tagTeams()
                ->map(fn (TagTeam $tagTeam): int => $tagTeam->id)
                ->values()
                ->all(),
        ];
    }
}
