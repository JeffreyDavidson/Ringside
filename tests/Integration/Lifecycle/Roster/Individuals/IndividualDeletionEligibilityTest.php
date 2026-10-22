<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Lifecycle\Roster\Individuals\IndividualDeletionEligibility;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

describe('booked wrestler deletion', function (): void {
    it('rejects a wrestler booked in a match that is upcoming or has no result', function (
        string $eventState,
        ?MatchFinish $finish,
    ): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        EventMatch::factory()
            ->forEvent(Event::factory()->{$eventState}()->create())
            ->withCompetitors([$wrestler, Wrestler::factory()->create()])
            ->create(['match_finish' => $finish]);
        $eligibility = resolve(IndividualDeletionEligibility::class);

        // Act & Assert
        $reason = null;

        try {
            $eligibility->ensureCanDelete($wrestler);
        } catch (CannotBeDeletedException $exception) {
            $reason = $exception->reason();
        }

        expect($reason)->toBe(BusinessRuleReason::BookedInMatch);
    })->with([
        'upcoming without result' => ['scheduled', null],
        'unscheduled without result' => ['unscheduled', null],
        'past without result' => ['past', null],
        'upcoming with result' => ['scheduled', MatchFinish::Pinfall],
    ]);

    it('allows a wrestler who only appears in resulted past matches', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        EventMatch::factory()
            ->forEvent(Event::factory()->past()->create())
            ->withCompetitors([$wrestler, Wrestler::factory()->create()])
            ->create(['match_finish' => MatchFinish::Pinfall]);
        $eligibility = resolve(IndividualDeletionEligibility::class);

        // Act & Assert
        expect(fn () => $eligibility->ensureCanDelete($wrestler))
            ->not->toThrow(CannotBeDeletedException::class);
    });

    it('allows a wrestler who is not booked', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $eligibility = resolve(IndividualDeletionEligibility::class);

        // Act & Assert
        expect(fn () => $eligibility->ensureCanDelete($wrestler))
            ->not->toThrow(CannotBeDeletedException::class);
    });
});

describe('booked referee deletion', function (): void {
    it('rejects a referee booked in a match that is upcoming or has no result', function (
        string $eventState,
        ?MatchFinish $finish,
    ): void {
        // Arrange
        $referee = Referee::factory()->create();
        EventMatch::factory()
            ->forEvent(Event::factory()->{$eventState}()->create())
            ->create(['match_finish' => $finish])
            ->referees()
            ->attach($referee);
        $eligibility = resolve(IndividualDeletionEligibility::class);

        // Act & Assert
        $reason = null;

        try {
            $eligibility->ensureCanDelete($referee);
        } catch (CannotBeDeletedException $exception) {
            $reason = $exception->reason();
        }

        expect($reason)->toBe(BusinessRuleReason::BookedInMatch);
    })->with([
        'upcoming without result' => ['scheduled', null],
        'unscheduled without result' => ['unscheduled', null],
        'past without result' => ['past', null],
        'upcoming with result' => ['scheduled', MatchFinish::Pinfall],
    ]);

    it('allows a referee who only officiated resulted past matches', function (): void {
        // Arrange
        $referee = Referee::factory()->create();
        EventMatch::factory()
            ->forEvent(Event::factory()->past()->create())
            ->create(['match_finish' => MatchFinish::Pinfall])
            ->referees()
            ->attach($referee);
        $eligibility = resolve(IndividualDeletionEligibility::class);

        // Act & Assert
        expect(fn () => $eligibility->ensureCanDelete($referee))
            ->not->toThrow(CannotBeDeletedException::class);
    });

    it('allows a referee who is not booked', function (): void {
        // Arrange
        $referee = Referee::factory()->create();
        $eligibility = resolve(IndividualDeletionEligibility::class);

        // Act & Assert
        expect(fn () => $eligibility->ensureCanDelete($referee))
            ->not->toThrow(CannotBeDeletedException::class);
    });
});

it('never treats a manager as booked in a match', function (): void {
    // Arrange
    $manager = Manager::factory()->create();
    $eligibility = resolve(IndividualDeletionEligibility::class);

    // Act & Assert
    expect(fn () => $eligibility->ensureCanDelete($manager))
        ->not->toThrow(CannotBeDeletedException::class);
});

it('names a booked referee by full name when refusing the deletion', function (): void {
    // Arrange
    $referee = Referee::factory()
        ->create(['first_name' => 'Earl', 'last_name' => 'Hebner'])
        ->refresh();
    EventMatch::factory()
        ->forEvent(Event::factory()->scheduled()->create())
        ->create()
        ->referees()
        ->attach($referee);
    $eligibility = resolve(IndividualDeletionEligibility::class);

    // Act & Assert
    expect(fn () => $eligibility->ensureCanDelete($referee))
        ->toThrow(CannotBeDeletedException::class, "Referee 'Earl Hebner' cannot be deleted");
});

it('names an already deleted manager by full name when refusing the deletion', function (): void {
    // Arrange
    $manager = Manager::factory()->create(['first_name' => 'Paul', 'last_name' => 'Heyman']);
    $manager->delete();
    $manager->refresh();
    $eligibility = resolve(IndividualDeletionEligibility::class);

    // Act & Assert
    expect(fn () => $eligibility->ensureCanDelete($manager))
        ->toThrow(CannotBeDeletedException::class, "Manager 'Paul Heyman' cannot be deleted because it is already deleted.");
});
