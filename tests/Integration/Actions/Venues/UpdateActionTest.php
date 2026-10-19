<?php

declare(strict_types=1);

use App\Actions\Venues\UpdateAction;
use App\Data\Events\VenueData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Venues\CannotBeUpdatedException;
use App\Exceptions\Venues\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;

test('it updates a venue while preserving its identity', function (): void {
    $venue = Venue::factory()->create();
    $data = new VenueData('Updated Arena', '100 New Street', 'Austin', 'Texas', '78701');

    $updatedVenue = resolve(UpdateAction::class)->handle($venue, $data);

    expect($updatedVenue)
        ->id->toBe($venue->id)
        ->name->toBe('Updated Arena')
        ->street_address->toBe('100 New Street')
        ->city->toBe('Austin')
        ->state->toBe('Texas')
        ->zipcode->toBe('78701');
});

describe('venue name guard', function (): void {
    test('it locks the new name before the venue row', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Original Arena']);
        $lock = resolve(RecordNameLock::class);

        // Act
        $statements = recordStatements(fn () => resolve(UpdateAction::class)->handle($venue, new VenueData(' Updated Arena ', '100 New Street', 'Austin', 'Texas', '78701')));

        // Assert
        $lockPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"'));
        $venueLockPosition = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "venues"'));

        expect($statements[$lockPosition]['bindings'])->toBe([$lock->key(GuardedName::VenueName, null, 'Updated Arena')])
            ->and($lockPosition)->toBeLessThan($venueLockPosition)
            ->and($venue->refresh()->name)->toBe('Updated Arena');
    });

    test('it rejects a name another venue already uses, even with surrounding space or deleted', function (string $name, bool $deleted): void {
        // Arrange
        $other = Venue::factory()->create(['name' => 'Madison Square Garden']);
        $venue = Venue::factory()->create(['name' => 'Original Arena']);

        if ($deleted) {
            $other->delete();
        }

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($venue, new VenueData($name, '100 New Street', 'Austin', 'Texas', '78701'));

        // Assert
        expect($update)->toThrow(NameTakenException::class, "A venue named 'Madison Square Garden' already exists.")
            ->and($venue->refresh()->name)->toBe('Original Arena');
    })->with([
        'exact' => ['Madison Square Garden', false],
        'leading space' => [' Madison Square Garden', false],
        'deleted' => ['Madison Square Garden', true],
    ]);

    test('it keeps the name of the venue being updated', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Same Arena']);

        // Act
        resolve(UpdateAction::class)->handle($venue, new VenueData('Same Arena', '100 New Street', 'Austin', 'Texas', '78701'));

        // Assert
        expect($venue->refresh()->city)->toBe('Austin');
    });
});

describe('venue time zone guard', function (): void {
    test('it rejects a time zone that puts two live events on one venue day, writing nothing', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Original Arena', 'timezone' => 'America/New_York']);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2026-11-20 02:00:00', 'UTC')]);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2026-11-20 20:00:00', 'UTC')]);
        $data = new VenueData('Renamed Arena', '100 New Street', 'Austin', 'Texas', '78701', 'UTC');

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($venue, $data);

        // Assert
        expect($update)->toThrow(CannotBeUpdatedException::class, "Venue '{$venue->name}' cannot change to the UTC time zone because it would host more than one event on Nov 20, 2026. Move or delete the extra events first.")
            ->and($venue->refresh()->timezone)->toBe('America/New_York')
            ->and($venue->name)->toBe('Original Arena');
    });

    test('it changes the time zone when live events stay on separate days and ignores deleted events', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['timezone' => 'America/New_York']);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2026-11-20 02:00:00', 'UTC')]);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2026-11-21 20:00:00', 'UTC')]);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2026-11-20 20:00:00', 'UTC')])->delete();
        $data = new VenueData('Updated Arena', '100 New Street', 'Austin', 'Texas', '78701', 'UTC');

        // Act
        resolve(UpdateAction::class)->handle($venue, $data);

        // Assert
        expect($venue->refresh()->timezone)->toBe('UTC');
    });

    test('it skips the check when the time zone is unchanged', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['timezone' => 'UTC']);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2026-11-20 02:00:00', 'UTC')]);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2026-11-20 20:00:00', 'UTC')]);
        $data = new VenueData('Updated Arena', '100 New Street', 'Austin', 'Texas', '78701', 'UTC');

        // Act
        resolve(UpdateAction::class)->handle($venue, $data);

        // Assert
        expect($venue->refresh()->name)->toBe('Updated Arena');
    });
});
