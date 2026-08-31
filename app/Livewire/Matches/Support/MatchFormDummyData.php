<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\MatchType;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

final class MatchFormDummyData
{
    public function __construct(private readonly RosterBookingEligibility $bookingEligibility) {}

    public function fill(CreateEditForm $form): void
    {
        $wrestlerIds = Wrestler::query()
            ->employed()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => $this->bookingEligibility->allows($wrestler))
            ->shuffle()
            ->take(2)
            ->map(fn (Wrestler $wrestler): int => $wrestler->id)
            ->all();

        $refereeIds = Referee::query()
            ->employed()
            ->get()
            ->filter(fn (Referee $referee): bool => $this->bookingEligibility->allows($referee))
            ->shuffle()
            ->take(1)
            ->map(fn (Referee $referee): int => $referee->id)
            ->all();

        $form->matchType = MatchType::Singles;
        $form->competitors = [
            ['wrestlers' => array_slice($wrestlerIds, 0, 1), 'tag_teams' => []],
            ['wrestlers' => array_slice($wrestlerIds, 1, 1), 'tag_teams' => []],
        ];
        $form->referees = $refereeIds;
        $form->titles = fake()->boolean(30)
            ? Title::query()->active()->inRandomOrder()->limit(1)->get(['id'])
                ->map(fn (Title $title): int => $title->id)
                ->all()
            : [];
        $form->preview = fake()->paragraph(2);
    }
}
