<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RestoreAction;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it restores a soft-deleted wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    $wrestler->delete(); // Soft delete

    expect($wrestler->trashed())->toBeTrue();

    RestoreAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();

    // Verify wrestler is restored
    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'name' => $wrestler->name,
        'deleted_at' => null,
    ]);
});

test('it restores wrestler with specific restore date', function () {
    $wrestler = Wrestler::factory()->create();
    $wrestler->delete(); // Soft delete
    $restoreDate = now()->subDays(1);

    expect($wrestler->trashed())->toBeTrue();

    RestoreAction::run($wrestler, $restoreDate);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();

    // Note: Laravel restore() always sets deleted_at to null
    // The custom date would be used for business logic, not the actual deleted_at field
    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'deleted_at' => null,
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->create();
    $wrestler->delete(); // Soft delete

    // Test with null date (should use now())
    RestoreAction::run($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();

    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'deleted_at' => null,
    ]);
});

test('it restores wrestler without automatically restoring relationships', function () {
    // Create wrestler with employment and manager relationships
    $wrestler = Wrestler::factory()->employed()->create();

    // End employment and manager relationships (simulate what happens during deletion)
    $wrestler->employments()->whereNull('ended_at')->update(['ended_at' => now()->subDays(5)]);
    $wrestler->managers()->wherePivot('fired_at', null)->updateExistingPivot(
        $wrestler->managers->first()->id ?? 1,
        ['fired_at' => now()->subDays(5)]
    );

    $wrestler->delete(); // Soft delete

    expect($wrestler->trashed())->toBeTrue();
    expect($wrestler->isEmployed())->toBeFalse();

    RestoreAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();
    expect($wrestler->isEmployed())->toBeFalse(); // Should remain unemployed

    // Verify wrestler is restored but relationships remain ended
    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'deleted_at' => null,
    ]);

    // Employment should still be ended
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->subDays(5)->toDateTimeString(),
    ]);
});

test('it maintains historical data integrity', function () {
    $wrestler = Wrestler::factory()->create();

    // Create historical employment records
    $wrestler->employments()->create([
        'started_at' => now()->subDays(100),
        'ended_at' => now()->subDays(80),
    ]);

    $wrestler->employments()->create([
        'started_at' => now()->subDays(50),
        'ended_at' => now()->subDays(10), // Ended before deletion
    ]);

    $wrestler->delete(); // Soft delete

    RestoreAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();

    // All historical records should be preserved
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(100)->toDateTimeString(),
        'ended_at' => now()->subDays(80)->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(50)->toDateTimeString(),
        'ended_at' => now()->subDays(10)->toDateTimeString(),
    ]);

    // Should have exactly 2 employment records
    expect($wrestler->employments()->count())->toBe(2);
});

test('it prevents restoring non-deleted wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler->trashed())->toBeFalse();

    expect(fn () => RestoreAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it restores wrestler with complex status history', function () {
    $wrestler = Wrestler::factory()->create();

    // Create complex status history
    $wrestler->employments()->create([
        'started_at' => now()->subDays(100),
        'ended_at' => now()->subDays(80),
    ]);

    $wrestler->retirements()->create([
        'started_at' => now()->subDays(70),
        'ended_at' => now()->subDays(50),
    ]);

    $wrestler->employments()->create([
        'started_at' => now()->subDays(40),
        'ended_at' => now()->subDays(20), // Ended before deletion
    ]);

    $wrestler->suspensions()->create([
        'started_at' => now()->subDays(15),
        'ended_at' => now()->subDays(5), // Ended before deletion
        'notes' => 'Final suspension',
    ]);

    $wrestler->delete(); // Soft delete

    RestoreAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();

    // All historical status records should be preserved
    expect($wrestler->employments()->count())->toBe(2);
    expect($wrestler->retirements()->count())->toBe(1);
    expect($wrestler->suspensions()->count())->toBe(1);

    // Wrestler should be in clean unemployed state
    expect($wrestler->isEmployed())->toBeFalse();
    expect($wrestler->isRetired())->toBeFalse();
    expect($wrestler->isSuspended())->toBeFalse();
    expect($wrestler->isInjured())->toBeFalse();
});

test('it allows wrestler to be re-employed after restoration', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // End employment before deletion
    $wrestler->employments()->whereNull('ended_at')->update(['ended_at' => now()->subDays(5)]);
    $wrestler->delete(); // Soft delete

    RestoreAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();
    expect($wrestler->isEmployed())->toBeFalse();

    // After restoration, wrestler can be employed again using EmployAction
    // This test verifies the wrestler is in a valid state for future employment
    expect($wrestler->canBeEmployed())->toBeTrue();
});

test('it preserves wrestler identity and metadata', function () {
    $originalName = 'Stone Cold Steve Austin';
    $originalHometown = 'Austin, Texas';
    $originalWeight = 252;

    $wrestler = Wrestler::factory()->create([
        'name' => $originalName,
        'hometown' => $originalHometown,
        'weight' => $originalWeight,
    ]);

    $originalId = $wrestler->id;
    $wrestler->delete(); // Soft delete

    RestoreAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();

    // All original data should be preserved
    expect($wrestler->id)->toBe($originalId);
    expect($wrestler->name)->toBe($originalName);
    expect($wrestler->hometown)->toBe($originalHometown);
    expect($wrestler->weight)->toBe($originalWeight);
});

test('it handles wrestler with no relationships', function () {
    $wrestler = Wrestler::factory()->create(); // No relationships
    $wrestler->delete(); // Soft delete

    expect($wrestler->trashed())->toBeTrue();
    expect($wrestler->employments)->toBeEmpty();
    expect($wrestler->managers)->toBeEmpty();

    RestoreAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeFalse();

    // Should successfully restore even with no relationships
    $this->assertDatabaseHas('wrestlers', [
        'id' => $wrestler->id,
        'deleted_at' => null,
    ]);
});
