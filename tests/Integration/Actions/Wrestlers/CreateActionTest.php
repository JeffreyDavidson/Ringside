<?php

declare(strict_types=1);

use App\Actions\Wrestlers\CreateAction;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\Wrestlers\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\Height;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('it creates a wrestler with basic information', function () {
    $data = new WrestlerData(
        name: 'John Cena',
        height: 73, // 6'1" = 73 inches
        weight: 251,
        hometown: 'West Newbury, Massachusetts',
        signature_move: 'Attitude Adjustment',
        employment_date: null
    );

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Wrestler::class)
        ->and($result->name)->toBe('John Cena')
        ->and($result->height->feet)->toBe(6)
        ->and($result->height->inches)->toBe(1)
        ->and($result->hometown)->toBe('West Newbury, Massachusetts')
        ->and($result->weight->toPounds())->toBe(251)
        ->and($result->signature_move)->toBe('Attitude Adjustment');

    assertDatabaseHas('wrestlers', [
        'name' => 'John Cena',
        'hometown' => 'West Newbury, Massachusetts',
        'weight' => 251,
        'signature_move' => 'Attitude Adjustment',
    ]);

    // Should not create employment record when no employment date provided
    assertDatabaseMissing('employments', [
        'employable_id' => $result->id,
    ]);
});

test('it creates a wrestler with employment when employment date is provided', function () {
    $employmentDate = now();

    $data = new WrestlerData(
        name: 'The Rock',
        height: 77, // 6'5" = 77 inches
        weight: 260,
        hometown: 'Miami, Florida',
        signature_move: 'Rock Bottom',
        employment_date: $employmentDate
    );

    $result = resolve(CreateAction::class)->handle($data);

    expect($result->name)->toBe('The Rock')
        ->and($result->currentEmployment()->exists())->toBeTrue();

    assertDatabaseHas('wrestlers', [
        'name' => 'The Rock',
        'hometown' => 'Miami, Florida',
        'weight' => 260,
        'signature_move' => 'Rock Bottom',
    ]);

    // Should create employment record
    assertDatabaseHas('employments', [
        'employable_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it creates wrestler with all optional fields', function () {
    $employmentDate = now();

    $data = new WrestlerData(
        name: 'Stone Cold Steve Austin',
        height: 74, // 6'2" = 74 inches
        weight: 252,
        hometown: 'Austin, Texas',
        signature_move: 'Stone Cold Stunner',
        employment_date: $employmentDate
    );

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Wrestler::class)
        ->and($result->name)->toBe('Stone Cold Steve Austin')
        ->and($result->height->feet)->toBe(6)
        ->and($result->height->inches)->toBe(2)
        ->and($result->hometown)->toBe('Austin, Texas')
        ->and($result->weight->toPounds())->toBe(252)
        ->and($result->signature_move)->toBe('Stone Cold Stunner');

    // Verify database state
    assertDatabaseHas('wrestlers', [
        'id' => $result->id,
        'name' => 'Stone Cold Steve Austin',
        'hometown' => 'Austin, Texas',
        'weight' => 252,
        'signature_move' => 'Stone Cold Stunner',
    ]);

    assertDatabaseHas('employments', [
        'employable_id' => $result->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles height conversion correctly', function () {
    $data = new WrestlerData(
        name: 'Test Wrestler',
        height: 71, // 5'11" = 71 inches
        weight: 200,
        hometown: 'Test City',
        signature_move: 'Test Move',
        employment_date: null
    );

    $result = resolve(CreateAction::class)->handle($data);

    expect($result->height)->toBeInstanceOf(Height::class)
        ->and($result->height->feet)->toBe(5)
        ->and($result->height->inches)->toBe(11)
        ->and($result->height->toInches())->toBe(71); // 5'11" = 71 inches
});

test('it assigns managers without employing them when the wrestler is not employed', function () {
    $managers = Manager::factory()->count(2)->create();

    $wrestler = resolve(CreateAction::class)->handle(new WrestlerData(
        name: 'Managed Wrestler',
        height: 72,
        weight: 225,
        hometown: 'Test City',
        signature_move: null,
        employment_date: null,
        managers: $managers,
    ));

    expect($wrestler->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing($managers->modelKeys())
        ->and($managers->every(fn (Manager $manager): bool => ! $manager->currentEmployment()->exists()))
        ->toBeTrue();
});

test('it employs assigned managers through the wrestler employment cascade', function () {
    $manager = Manager::factory()->create();
    $employmentDate = now()->subDay();

    $wrestler = resolve(CreateAction::class)->handle(new WrestlerData(
        name: 'Employed Managed Wrestler',
        height: 72,
        weight: 225,
        hometown: 'Test City',
        signature_move: null,
        employment_date: $employmentDate,
        managers: new Collection([$manager]),
    ));

    expect($wrestler->currentEmployment()->exists())->toBeTrue()
        ->and($manager->currentEmployment()->exists())->toBeTrue();

    assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

function namedWrestlerData(string $name, ?string $signatureMove = null): WrestlerData
{
    return new WrestlerData(
        name: $name,
        height: 72,
        weight: 225,
        hometown: 'Test City',
        signature_move: $signatureMove,
        employment_date: null,
    );
}

describe('wrestler name guard', function (): void {
    test('it locks the name and the signature move of the promotion before inserting the wrestler', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);
        $data = namedWrestlerData('  The Rock ', 'Rock Bottom');
        $lock = resolve(RecordNameLock::class);

        // Act
        $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

        // Assert
        $lockPositions = array_keys(array_filter($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"')));
        $insertPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "wrestlers"'));

        expect(array_map(fn (int $position): array => $statements[$position]['bindings'], $lockPositions))->toBe([
            [$lock->key(GuardedName::WrestlerName, $promotion->id, 'The Rock')],
            [$lock->key(GuardedName::WrestlerSignatureMove, $promotion->id, 'Rock Bottom')],
        ])
            ->and(array_last($lockPositions))->toBeLessThan($insertPosition);
    });

    test('it takes no signature move lock for a wrestler without one', function () {
        // Arrange
        $data = namedWrestlerData('The Rock');

        // Act
        resolve(CreateAction::class)->handle($data);

        // Assert
        expect(DB::table('record_name_locks')->count())->toBe(1);
    });

    test('it stores the trimmed name', function () {
        // Arrange
        $data = namedWrestlerData('  The Rock ');

        // Act
        $wrestler = resolve(CreateAction::class)->handle($data);

        // Assert
        expect($wrestler->name)->toBe('The Rock');
    });

    test('it rejects a name another wrestler of the promotion already uses, even with surrounding space or deleted', function (string $name, bool $deleted) {
        // Arrange
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);
        $existing = Wrestler::factory()->for($promotion, 'promotion')->create(['name' => 'The Rock']);

        if ($deleted) {
            $existing->delete();
        }

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(namedWrestlerData($name));

        // Assert
        expect($create)->toThrow(NameTakenException::class, "A wrestler named 'The Rock' already exists in this promotion.")
            ->and(Wrestler::query()->withoutGlobalScopes()->where('name', 'The Rock')->count())->toBe(1);
    })->with([
        'exact' => ['The Rock', false],
        'leading space' => [' The Rock', false],
        'deleted' => ['The Rock', true],
    ]);

    test('it rejects a name an unowned wrestler already uses when creating without a promotion', function () {
        // Arrange
        Wrestler::factory()->create(['name' => 'The Rock']);

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(namedWrestlerData('The Rock'));

        // Assert
        expect($create)->toThrow(NameTakenException::class)
            ->and(Wrestler::query()->withoutGlobalScopes()->where('name', 'The Rock')->count())->toBe(1);
    });

    test('it rejects a signature move another wrestler of the promotion already uses', function () {
        // Arrange
        Wrestler::factory()->create(['name' => 'The Rock', 'signature_move' => 'Rock Bottom']);

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(namedWrestlerData('Stone Cold', 'Rock Bottom'));

        // Assert
        expect($create)->toThrow(NameTakenException::class, "A wrestler with the signature move 'Rock Bottom' already exists in this promotion.")
            ->and(Wrestler::query()->where('name', 'Stone Cold')->exists())->toBeFalse();
    });

    test('it allows a name and signature move only another promotion uses', function () {
        // Arrange
        Wrestler::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'The Rock', 'signature_move' => 'Rock Bottom']);
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);

        // Act
        $wrestler = resolve(CreateAction::class)->handle(namedWrestlerData('The Rock', 'Rock Bottom'));

        // Assert
        expect($wrestler->promotion_id)->toBe($promotion->id);
    });
});
