<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\Events\EventRosterSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

describe('event roster scheduling eligibility', function (): void {
    it('allows a date when nobody on the card is unavailable', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'UTC']);
        $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 20:00:00']);
        $person = Wrestler::factory()->bookable()->for($promotion)->create();
        EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();

        // Act
        $ensure = fn () => resolve(EventRosterSchedulingEligibility::class)->ensureAvailableAt($event, now()->parse('2026-10-24 20:00:00'));

        // Assert
        expect($ensure)->not->toThrow(EntityNotAvailableException::class);
    });

    it('rejects a date on which a wrestler, referee or tag team member on the card is unavailable', function (string $kind): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'UTC']);
        $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 20:00:00']);
        $wrestler = Wrestler::factory()->bookable()->for($promotion)->create();
        $referee = Referee::factory()->bookable()->for($promotion)->create();
        $team = TagTeam::factory()->bookable()->for($promotion)->create();
        $member = Wrestler::factory()->bookable()->for($promotion)->create();
        $team->wrestlers()->attach($member, ['joined_at' => now()->subYear()]);
        $match = EventMatch::factory()->forEvent($event)->withCompetitors([$wrestler])->create();
        $match->referees()->attach($referee);
        EventMatch::factory()->forEvent($event)->withCompetitors([$team])->create();
        $unavailable = match ($kind) {
            'wrestler' => $wrestler,
            'referee' => $referee,
            default => $member,
        };
        resolve(CreateAction::class)->handle($unavailable, '2026-10-24', '2026-10-24', null, administrator());

        // Act
        $ensure = fn () => resolve(EventRosterSchedulingEligibility::class)->ensureAvailableAt($event, now()->parse('2026-10-24 20:00:00'));

        // Assert
        expect($ensure)->toThrow(EntityNotAvailableException::class);
    })->with(['wrestler', 'referee', 'tag team member']);

    it('has nothing to check without a new date', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 20:00:00']);
        $person = Wrestler::factory()->bookable()->for($promotion)->create();
        EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();
        resolve(CreateAction::class)->handle($person, '2026-10-17', '2026-10-17', null, administrator());

        // Act
        $statements = recordStatements(fn () => DB::transaction(
            fn () => resolve(EventRosterSchedulingEligibility::class)->ensureAvailableAt($event, null, lock: true),
        ));

        // Assert
        expect($statements)->toBeEmpty();
    });

    it('locks the roster on the card, referees then tag teams then wrestlers, before checking availability', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'UTC']);
        $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 20:00:00']);
        $wrestler = Wrestler::factory()->bookable()->for($promotion)->create();
        $referee = Referee::factory()->bookable()->for($promotion)->create();
        $team = TagTeam::factory()->bookable()->for($promotion)->create();
        $member = Wrestler::factory()->bookable()->for($promotion)->create();
        $team->wrestlers()->attach($member, ['joined_at' => now()->subYear()]);
        $match = EventMatch::factory()->forEvent($event)->withCompetitors([$wrestler])->create();
        $match->referees()->attach($referee);
        EventMatch::factory()->forEvent($event)->withCompetitors([$team])->create();

        // Act
        $statements = recordStatements(fn () => DB::transaction(
            fn () => resolve(EventRosterSchedulingEligibility::class)->ensureAvailableAt($event, now()->parse('2026-10-24 20:00:00'), lock: true),
        ));

        // Assert
        $locks = collect($statements)->filter(fn (array $statement): bool => $statement['locked'])
            ->map(fn (array $statement): string => str_contains($statement['sql'], 'from "referees"') ? 'referees' : (str_contains($statement['sql'], 'from "tag_teams"') ? 'tag_teams' : 'wrestlers'))
            ->values()->all();
        $firstAvailabilityRead = collect($statements)->search(fn (array $statement): bool => str_contains($statement['sql'], 'roster_unavailabilities'));
        $lastLock = collect($statements)->keys()->filter(fn (int $index): bool => $statements[$index]['locked'])->last();
        expect($locks)->toBe(['referees', 'tag_teams', 'wrestlers'])
            ->and($lastLock)->toBeLessThan((int) $firstAvailabilityRead);
    });

    it('does not lock anything when asked only to check', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'UTC']);
        $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 20:00:00']);
        $person = Wrestler::factory()->bookable()->for($promotion)->create();
        EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();

        // Act
        $statements = recordStatements(fn () => resolve(EventRosterSchedulingEligibility::class)->ensureAvailableAt($event, now()->parse('2026-10-24 20:00:00')));

        // Assert
        expect(collect($statements)->contains(fn (array $statement): bool => $statement['locked']))->toBeFalse();
    });
});
