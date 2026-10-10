<?php

declare(strict_types=1);

use App\Actions\Venues\CreateAction;
use App\Data\Events\VenueData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Venues\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;

test('it creates a venue with its address data', function (): void {
    $data = new VenueData('Madison Square Garden', '4 Pennsylvania Plaza', 'New York', 'New York', '10001');

    $venue = resolve(CreateAction::class)->handle($data);

    expect($venue)
        ->toBeInstanceOf(Venue::class)
        ->name->toBe('Madison Square Garden')
        ->street_address->toBe('4 Pennsylvania Plaza')
        ->city->toBe('New York')
        ->state->toBe('New York')
        ->zipcode->toBe('10001');
});

function namedVenueData(string $name): VenueData
{
    return new VenueData($name, '4 Pennsylvania Plaza', 'New York', 'New York', '10001');
}

describe('venue name guard', function (): void {
    test('it locks the name before inserting the venue', function (): void {
        // Arrange
        $data = namedVenueData('  Madison Square Garden ');
        $lock = resolve(RecordNameLock::class);

        // Act
        $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

        // Assert
        $lockPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"'));
        $insertPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "venues"'));

        expect($statements[$lockPosition]['bindings'])->toBe([$lock->key(GuardedName::VenueName, null, 'Madison Square Garden')])
            ->and($lockPosition)->toBeLessThan($insertPosition);
    });

    test('it stores the trimmed name', function (): void {
        // Arrange
        $data = namedVenueData('  Madison Square Garden ');

        // Act
        $venue = resolve(CreateAction::class)->handle($data);

        // Assert
        expect($venue->name)->toBe('Madison Square Garden');
    });

    test('it rejects a name another venue already uses, even with surrounding space or deleted', function (string $name, bool $deleted): void {
        // Arrange
        $existing = Venue::factory()->create(['name' => 'Madison Square Garden']);

        if ($deleted) {
            $existing->delete();
        }

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(namedVenueData($name));

        // Assert
        expect($create)->toThrow(NameTakenException::class, "A venue named 'Madison Square Garden' already exists.")
            ->and(Venue::query()->withTrashed()->where('name', 'Madison Square Garden')->count())->toBe(1);
    })->with([
        'exact' => ['Madison Square Garden', false],
        'leading space' => [' Madison Square Garden', false],
        'deleted' => ['Madison Square Garden', true],
    ]);

    test('it rejects a name regardless of the promotion the request acts in', function (): void {
        // Arrange
        Venue::factory()->create(['name' => 'Madison Square Garden']);
        enforcePromotionContext(Promotion::factory()->create());

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(namedVenueData('Madison Square Garden'));

        // Assert
        expect($create)->toThrow(NameTakenException::class)
            ->and(Venue::query()->withTrashed()->where('name', 'Madison Square Garden')->count())->toBe(1);
    });
});
