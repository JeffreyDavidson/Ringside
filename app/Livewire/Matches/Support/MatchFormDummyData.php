<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\Matches\MatchType;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

final readonly class MatchFormDummyData
{
    public function __construct(private RosterBookingEligibility $bookingEligibility) {}

    public function fill(CreateEditForm $form): void
    {
        $wrestlerIds = Wrestler::query()
            ->employed()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => $this->bookingEligibility->allows($wrestler))
            ->shuffle()
            ->take(2)
            ->modelKeys();

        $refereeIds = Referee::query()
            ->employed()
            ->get()
            ->filter(fn (Referee $referee): bool => $this->bookingEligibility->allows($referee))
            ->shuffle()
            ->take(1)
            ->modelKeys();

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
