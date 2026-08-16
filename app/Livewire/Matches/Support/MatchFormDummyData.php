<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\MatchType;
use App\Lifecycle\RosterBookingEligibility;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;

final class MatchFormDummyData
{
    /**
     * @return array{
     *     matchType: MatchType,
     *     competitors: array<int, array{wrestlers: array<int>, tag_teams: array<int>}>,
     *     referees: array<int>,
     *     titles: array<int>,
     *     preview: string
     * }
     */
    public function generate(): array
    {
        $wrestlerIds = Wrestler::query()
            ->employed()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => RosterBookingEligibility::allows($wrestler))
            ->shuffle()
            ->take(2)
            ->modelKeys();

        $refereeIds = Referee::query()
            ->employed()
            ->get()
            ->filter(fn (Referee $referee): bool => RosterBookingEligibility::allows($referee))
            ->shuffle()
            ->take(1)
            ->modelKeys();

        return [
            'matchType' => MatchType::Singles,
            'competitors' => [
                ['wrestlers' => array_slice($wrestlerIds, 0, 1), 'tag_teams' => []],
                ['wrestlers' => array_slice($wrestlerIds, 1, 1), 'tag_teams' => []],
            ],
            'referees' => $refereeIds,
            'titles' => fake()->boolean(30)
                ? Title::query()->active()->inRandomOrder()->limit(1)->pluck('id')->all()
                : [],
            'preview' => fake()->paragraph(2),
        ];
    }
}
