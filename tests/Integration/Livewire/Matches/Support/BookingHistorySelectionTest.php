<?php

declare(strict_types=1);

use App\Enums\Matches\MatchType;
use App\Livewire\Matches\Support\BookingHistorySelection;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Validation\ValidationException;

describe('booking history selection', function (): void {
    it('accepts competitors that fit the match type and belong to the event promotion', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
        [$team, $rival] = TagTeam::factory()->count(2)->for($promotion, 'promotion')->create()->all();

        // Act
        $singles = fn () => resolve(BookingHistorySelection::class)->validate($event, MatchType::Singles, [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]]);
        $tag = fn () => resolve(BookingHistorySelection::class)->validate($event, MatchType::TagTeam, [['tag_teams' => [$team->id]], ['tag_teams' => [$rival->id]]]);

        // Assert
        expect($singles)->not->toThrow(ValidationException::class)
            ->and($tag)->not->toThrow(ValidationException::class);
    });

    it('accepts a deleted competitor, whose history is still retained', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
        $first->delete();

        // Act
        $validate = fn () => resolve(BookingHistorySelection::class)->validate($event, MatchType::Singles, [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]]);

        // Assert
        expect($validate)->not->toThrow(ValidationException::class);
    });

    it('has nothing to validate for an event without a promotion', function (): void {
        // Arrange
        $event = Event::factory()->unscheduled()->create();

        // Act
        $validate = fn () => resolve(BookingHistorySelection::class)->validate($event, MatchType::Singles, []);

        // Assert
        expect($validate)->not->toThrow(ValidationException::class);
    });

    it('rejects foreign or nonexistent identities without naming them', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        $own = Wrestler::factory()->for($promotion, 'promotion')->create();
        $foreign = Wrestler::factory()->for(Promotion::factory(), 'promotion')->create();

        // Act
        $foreignSelection = fn () => resolve(BookingHistorySelection::class)->validate($event, MatchType::Singles, [['wrestlers' => [$own->id]], ['wrestlers' => [$foreign->id]]]);
        $missingSelection = fn () => resolve(BookingHistorySelection::class)->validate($event, MatchType::Singles, [['wrestlers' => [$own->id]], ['wrestlers' => [$foreign->id + 999]]]);

        // Assert
        expect($foreignSelection)->toThrow(ValidationException::class)
            ->and($missingSelection)->toThrow(ValidationException::class);
    });

    it('rejects prohibited identity types and undersized or empty selections', function (MatchType $type, array $selection, string $field): void {
        // Arrange
        $event = Event::factory()->for(Promotion::factory(), 'promotion')->scheduled()->create();

        // Act
        $errors = null;
        try {
            resolve(BookingHistorySelection::class)->validate($event, $type, $selection);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        }

        // Assert
        expect($errors)->not->toBeNull()
            ->and(array_key_first($errors ?? []))->toBe($field);
    })->with([
        'team in singles' => [MatchType::Singles, [['tag_teams' => [1]], ['wrestlers' => [1]]], 'history'],
        'too few rumble entrants' => [MatchType::RoyalRumble, [['wrestlers' => [1, 2, 3]]], 'history'],
        'an empty side' => [MatchType::Singles, [['wrestlers' => [1]], ['wrestlers' => []]], 'history'],
        'the wrong number of sides' => [MatchType::Singles, [['wrestlers' => [1]]], 'competitors'],
        'a competitor repeated across sides' => [MatchType::TagTeam, [['tag_teams' => [1]], ['tag_teams' => [1]]], 'competitors.0.tag_teams.0'],
    ]);
});
