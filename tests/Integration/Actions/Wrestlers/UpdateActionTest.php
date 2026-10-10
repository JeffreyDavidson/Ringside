<?php

declare(strict_types=1);

use App\Actions\Wrestlers\UpdateAction;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\Wrestlers\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('it updates wrestler basic information', function () {
    $wrestler = Wrestler::factory()->create([
        'name' => 'Original Name',
        'height' => 70,
        'weight' => 200,
        'hometown' => 'Original Town',
        'signature_move' => 'Original Move',
    ]);

    $updateData = new WrestlerData(
        name: 'Updated Name',
        height: 75,
        weight: 250,
        hometown: 'Updated Town',
        signature_move: 'Updated Move',
        employment_date: null,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    expect($result)->toBeInstanceOf(Wrestler::class)
        ->and($result->name)->toBe('Updated Name')
        ->and($result->height->toInches())->toBe(75)
        ->and($result->weight->toPounds())->toBe(250)
        ->and($result->hometown)->toBe('Updated Town')
        ->and($result->signature_move)->toBe('Updated Move');

    assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'name' => 'Updated Name',
        'height' => 75,
        'weight' => 250,
        'hometown' => 'Updated Town',
        'signature_move' => 'Updated Move',
    ]);
});

test('it updates using the current persisted wrestler state', function () {
    $wrestler = Wrestler::factory()->create([
        'name' => 'Original Name',
        'height' => 70,
        'weight' => 200,
    ]);
    $staleWrestler = $wrestler->replicate(['id']);
    $staleWrestler->id = $wrestler->id;
    $staleWrestler->exists = true;

    $updatedWrestler = resolve(UpdateAction::class)->handle(
        $staleWrestler,
        new WrestlerData(
            name: 'Updated From Stale State',
            height: 75,
            weight: 250,
            hometown: $wrestler->hometown,
            signature_move: $wrestler->signature_move,
            employment_date: null,
            managers: null,
        ),
    );
    $persistedWrestler = Wrestler::query()
        ->whereKey($wrestler->getKey())
        ->firstOrFail();

    expect($updatedWrestler->getKey())->toBe($wrestler->getKey())
        ->and($persistedWrestler->name)->toBe('Updated From Stale State');
});

test('it updates wrestler and employs them when employment date provided', function () {
    $wrestler = Wrestler::factory()->create();
    $employmentDate = now();

    expect($wrestler->currentEmployment()->exists())->toBeFalse();

    $updateData = new WrestlerData(
        name: 'John Cena',
        height: 73,
        weight: 251,
        hometown: 'West Newbury, MA',
        signature_move: 'Attitude Adjustment',
        employment_date: $employmentDate,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    $result->refresh();
    expect($result->name)->toBe('John Cena')
        ->and($result->currentEmployment()->exists())->toBeTrue();

    // Verify employment record was created via EmployAction
    assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it updates wrestler without employing when no employment date', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler->currentEmployment()->exists())->toBeFalse();

    $updateData = new WrestlerData(
        name: 'The Rock',
        height: 77,
        weight: 260,
        hometown: 'Miami, FL',
        signature_move: 'Rock Bottom',
        employment_date: null,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    $result->refresh();
    expect($result->name)->toBe('The Rock')
        ->and($result->currentEmployment()->exists())->toBeFalse();

    // Verify no employment record was created
    assertDatabaseMissing('employments', [
        'employable_id' => $wrestler->id,
    ]);
});

test('it does not re-employ already employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $originalEmployment = $wrestler->currentEmployment()->firstOrFail();

    expect($wrestler->currentEmployment()->exists())->toBeTrue();

    $updateData = new WrestlerData(
        name: 'Updated Name',
        height: 72,
        weight: 220,
        hometown: 'Updated Town',
        signature_move: 'Updated Move',
        employment_date: now(),
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    $result->refresh();
    expect($result->name)->toBe('Updated Name')
        ->and($result->currentEmployment()->exists())->toBeTrue();

    // Should still have only the original employment record
    expect($result->employments()->count())->toBe(1);
    expect($result->currentEmployment()->firstOrFail()->id)->toBe($originalEmployment->id);
});

test('it employs managers when wrestler gets employed', function () {
    $wrestler = Wrestler::factory()->create();
    $manager1 = Manager::factory()->create(); // unemployed
    $manager2 = Manager::factory()->employed()->create(); // already employed

    // Assign managers to wrestler
    $wrestler->managers()->attach($manager1->id, ['hired_at' => now()->subDays(5)]);
    $wrestler->managers()->attach($manager2->id, ['hired_at' => now()->subDays(3)]);

    expect($wrestler->currentEmployment()->exists())->toBeFalse()
        ->and($manager1->currentEmployment()->exists())->toBeFalse()
        ->and($manager2->currentEmployment()->exists())->toBeTrue();

    $employmentDate = now();
    $updateData = new WrestlerData(
        name: 'Updated Name',
        height: 74,
        weight: 230,
        hometown: 'Updated Town',
        signature_move: 'Updated Move',
        employment_date: $employmentDate,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    $result->refresh();
    $manager1->refresh();
    $manager2->refresh();

    expect($result->currentEmployment()->exists())->toBeTrue()
        ->and($manager1->currentEmployment()->exists())->toBeTrue(); // Should now be employed via cascade
    expect($manager2->currentEmployment()->exists())->toBeTrue(); // Should remain employed

    // Both wrestler and manager1 should have new employment records
    assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    assertDatabaseHas('employments', [
        'employable_id' => $manager1->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses the provided employment date', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Test Name',
        height: 70,
        weight: 200,
        hometown: 'Test Town',
        signature_move: 'Test Move',
        employment_date: now()->subDays(10), // Past date
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    $result->refresh();
    expect($result->currentEmployment()->exists())->toBeTrue();

    // The provided employment date should be persisted
    assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'started_at' => now()->subDays(10)->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it maintains transaction boundaries', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Transaction Test',
        height: 71,
        weight: 210,
        hometown: 'Transaction Town',
        signature_move: 'Transaction Move',
        employment_date: now(),
        managers: null
    );

    // Simulate transaction - all changes should be atomic
    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    $result->refresh();

    // Both wrestler update and employment should succeed together
    assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'name' => 'Transaction Test',
    ]);

    assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'ended_at' => null,
    ]);
});

test('it returns updated wrestler instance', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Return Test',
        height: 76,
        weight: 240,
        hometown: 'Return Town',
        signature_move: 'Return Move',
        employment_date: null,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    expect($result)->toBeInstanceOf(Wrestler::class)
        ->and($result->id)->toBe($wrestler->id)
        ->and($result->name)->toBe('Return Test')
        ->and($result->height->toInches())->toBe(76)
        ->and($result->weight->toPounds())->toBe(240);
});

test('it handles height conversion correctly', function () {
    $wrestler = Wrestler::factory()->create();

    $updateData = new WrestlerData(
        name: 'Height Test',
        height: 71, // 5'11"
        weight: 200,
        hometown: 'Height Town',
        signature_move: 'Height Move',
        employment_date: null,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    expect($result->height->feet)->toBe(5)
        ->and($result->height->inches)->toBe(11)
        ->and($result->height->toInches())->toBe(71);
});

test('it preserves wrestler id and timestamps', function () {
    $wrestler = Wrestler::factory()->create();
    $originalId = $wrestler->id;
    $originalCreatedAt = $wrestler->created_at;

    $updateData = new WrestlerData(
        name: 'Preserve Test',
        height: 72,
        weight: 205,
        hometown: 'Preserve Town',
        signature_move: 'Preserve Move',
        employment_date: null,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    expect($result->id)->toBe($originalId)
        ->and(requiredDate($result->created_at)->timestamp)->toBe(requiredDate($originalCreatedAt)->timestamp)
        ->and(requiredDate($result->updated_at)->timestamp)->toBeGreaterThanOrEqual(requiredDate($originalCreatedAt)->timestamp);
});

test('it handles null signature move', function () {
    $wrestler = Wrestler::factory()->create(['signature_move' => 'Original Move']);

    $updateData = new WrestlerData(
        name: 'Null Move Test',
        height: 70,
        weight: 200,
        hometown: 'Null Town',
        signature_move: null,
        employment_date: null,
        managers: null
    );

    $result = resolve(UpdateAction::class)->handle($wrestler, $updateData);

    expect($result->signature_move)->toBeNull();

    assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'signature_move' => null,
    ]);
});

function renamingWrestlerData(string $name, ?string $signatureMove): WrestlerData
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
    test('it locks the new name and signature move of the promotion before the wrestler row', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create(['name' => 'Original Name']);
        $lock = resolve(RecordNameLock::class);

        // Act
        $statements = recordStatements(fn () => resolve(UpdateAction::class)->handle($wrestler, renamingWrestlerData(' Updated Name ', 'Rock Bottom')));

        // Assert
        $lockPositions = array_keys(array_filter($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"')));
        $wrestlerLockPosition = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "wrestlers"'));

        expect(array_map(fn (int $position): array => $statements[$position]['bindings'], $lockPositions))->toBe([
            [$lock->key(GuardedName::WrestlerName, $promotion->id, 'Updated Name')],
            [$lock->key(GuardedName::WrestlerSignatureMove, $promotion->id, 'Rock Bottom')],
        ])
            ->and(array_last($lockPositions))->toBeLessThan($wrestlerLockPosition)
            ->and($wrestler->refresh()->name)->toBe('Updated Name');
    });

    test('it rejects a name another wrestler of the same promotion already uses, even with surrounding space or deleted', function (bool $inPromotion, string $name, bool $deleted) {
        // Arrange
        $promotion = $inPromotion ? Promotion::factory()->create() : null;
        $other = Wrestler::factory()->state(['promotion_id' => $promotion?->id, 'name' => 'The Rock'])->create();
        $wrestler = Wrestler::factory()->state(['promotion_id' => $promotion?->id, 'name' => 'Original Name'])->create();

        if ($deleted) {
            $other->delete();
        }

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($wrestler, renamingWrestlerData($name, null));

        // Assert
        expect($update)->toThrow(NameTakenException::class, "A wrestler named 'The Rock' already exists in this promotion.")
            ->and($wrestler->refresh()->name)->toBe('Original Name');
    })->with([
        'without a promotion' => [false, 'The Rock', false],
        'in a promotion' => [true, 'The Rock', false],
        'leading space' => [true, ' The Rock', false],
        'deleted' => [true, 'The Rock', true],
    ]);

    test('it rejects a signature move another wrestler already uses and writes nothing', function () {
        // Arrange
        Wrestler::factory()->create(['name' => 'The Rock', 'signature_move' => 'Rock Bottom']);
        $wrestler = Wrestler::factory()->create(['name' => 'Original Name', 'signature_move' => 'Original Move']);

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($wrestler, renamingWrestlerData('Renamed', 'Rock Bottom'));

        // Assert
        expect($update)->toThrow(NameTakenException::class, "A wrestler with the signature move 'Rock Bottom' already exists in this promotion.")
            ->and($wrestler->refresh()->name)->toBe('Original Name')
            ->and($wrestler->signature_move)->toBe('Original Move');
    });

    test('it keeps the name and signature move of the wrestler being updated and allows ones only another promotion uses', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        Wrestler::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Other Name', 'signature_move' => 'Other Move']);
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create(['name' => 'Same Name', 'signature_move' => 'Same Move']);

        // Act
        resolve(UpdateAction::class)->handle($wrestler, renamingWrestlerData('Same Name', 'Same Move'));
        resolve(UpdateAction::class)->handle($wrestler, renamingWrestlerData('Other Name', 'Other Move'));

        // Assert
        expect($wrestler->refresh()->name)->toBe('Other Name')
            ->and($wrestler->signature_move)->toBe('Other Move');
    });
});
