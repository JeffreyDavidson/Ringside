<?php

declare(strict_types=1);

use App\Actions\Matches\AddRefereesToMatchAction;
use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Actions\Matches\AddTitlesToMatchAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Collection;

dataset('standalone match assignments', [
    'wrestlers' => [
        'wrestlers',
        fn (Promotion $promotion): Collection => collect([Wrestler::factory()->bookable()->for($promotion, 'promotion')->create()]),
        fn (EventMatch $match, Collection $records) => resolve(AddWrestlersToMatchAction::class)->handle($match, $records, 1),
        fn (EventMatch $match): int => $match->competitors()->count(),
    ],
    'tag teams' => [
        'tag teams',
        fn (Promotion $promotion): Collection => collect([TagTeam::factory()->bookable()->for($promotion, 'promotion')->create()]),
        fn (EventMatch $match, Collection $records) => resolve(AddTagTeamsToMatchAction::class)->handle($match, $records, 1),
        fn (EventMatch $match): int => $match->competitors()->count(),
    ],
    'referees' => [
        'referees',
        fn (Promotion $promotion): Collection => collect([Referee::factory()->bookable()->for($promotion, 'promotion')->create()]),
        fn (EventMatch $match, Collection $records) => resolve(AddRefereesToMatchAction::class)->handle($match, $records),
        fn (EventMatch $match): int => $match->referees()->count(),
    ],
    'titles' => [
        'titles',
        fn (Promotion $promotion): Collection => collect([Title::factory()->active()->singles()->for($promotion, 'promotion')->create()]),
        fn (EventMatch $match, Collection $records) => resolve(AddTitlesToMatchAction::class)->handle($match, $records),
        fn (EventMatch $match): int => $match->titles()->count(),
    ],
]);

describe('standalone assignment promotion ownership', function (): void {
    it('rejects attaching :dataset from another promotion', function (string $entityType, Closure $makeRecords, Closure $assign, Closure $attachedCount): void {
        // Arrange
        [$home, $foreign] = Promotion::factory()->count(2)->create()->all();
        $match = EventMatch::factory()
            ->for(Event::factory()->for($home, 'promotion'))
            ->withCompetitors(Wrestler::factory()->bookable()->for($home, 'promotion')->count(2)->create()->all())
            ->create();
        $attachedBefore = $attachedCount($match);
        $records = $makeRecords($foreign);

        // Act
        $attempt = fn () => $assign($match, $records);

        // Assert
        expect($attempt)->toThrow(
            InvalidMatchConfigurationException::class,
            "Selected {$entityType} must all belong to the event's promotion.",
        );
        expect($attachedCount($match))->toBe($attachedBefore);
    })->with('standalone match assignments');

    it('attaches :dataset from the event promotion', function (string $entityType, Closure $makeRecords, Closure $assign, Closure $attachedCount): void {
        // Arrange
        $home = Promotion::factory()->create();
        $match = EventMatch::factory()
            ->for(Event::factory()->for($home, 'promotion'))
            ->withCompetitors(Wrestler::factory()->bookable()->for($home, 'promotion')->count(2)->create()->all())
            ->create();
        $attachedBefore = $attachedCount($match);
        $records = $makeRecords($home);

        // Act
        $assign($match, $records);

        // Assert
        expect($attachedCount($match))->toBe($attachedBefore + 1);
    })->with('standalone match assignments');
});
