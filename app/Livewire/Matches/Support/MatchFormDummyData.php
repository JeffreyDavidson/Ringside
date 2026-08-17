<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\MatchType;
use App\Lifecycle\RosterBookingEligibility;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

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
            ->map(fn (Wrestler $wrestler): int => $wrestler->id)
            ->all();

        $refereeIds = Referee::query()
            ->employed()
            ->get()
            ->filter(fn (Referee $referee): bool => RosterBookingEligibility::allows($referee))
            ->shuffle()
            ->take(1)
            ->map(fn (Referee $referee): int => $referee->id)
            ->all();

        return [
            'matchType' => MatchType::Singles,
            'competitors' => [
                ['wrestlers' => array_slice($wrestlerIds, 0, 1), 'tag_teams' => []],
                ['wrestlers' => array_slice($wrestlerIds, 1, 1), 'tag_teams' => []],
            ],
            'referees' => $refereeIds,
            'titles' => fake()->boolean(30)
                ? Title::query()->active()->inRandomOrder()->limit(1)->get(['id'])
                    ->map(fn (Title $title): int => $title->id)
                    ->all()
                : [],
            'preview' => fake()->paragraph(2),
        ];
    }
}
