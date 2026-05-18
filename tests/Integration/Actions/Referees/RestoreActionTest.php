<?php

declare(strict_types=1);

use App\Actions\Referees\RestoreAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Data\CannotBeRestoredException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it restores a soft-deleted referee', function () {
    $referee = Referee::factory()->create();
    $originalId = $referee->id;
    $referee->delete(); // Soft delete

    expect($referee->trashed())->toBeTrue();

    RestoreAction::run($referee);

    $referee->refresh();
    expect($referee->trashed())->toBeFalse();

    $this->assertDatabaseHas('referees', [
        'id' => $originalId,
        'first_name' => $referee->first_name,
        'last_name' => $referee->last_name,
        'deleted_at' => null,
    ]);
});

test('it validates referee can be restored', function () {
    $referee = Referee::factory()->create();
    $referee->delete(); // Soft delete

    expect($referee->trashed())->toBeTrue();

    // Should succeed without throwing validation exception
    RestoreAction::run($referee);

    $referee->refresh();
    expect($referee->trashed())->toBeFalse();
});

test('it throws exception when referee cannot be restored', function () {
    $referee = Referee::factory()->create(); // Not soft deleted

    expect($referee->trashed())->toBeFalse();

    expect(fn () => RestoreAction::run($referee))
        ->toThrow(CannotBeRestoredException::class);
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->create();
    $referee->delete(); // Soft delete

    RestoreAction::run($referee);

    $referee->refresh();

    // Restoration should be atomic
    expect($referee->trashed())->toBeFalse();
    expect($referee->deleted_at)->toBeNull();
});

test('it preserves referee data after restoration', function () {
    $referee = Referee::factory()->create([
        'first_name' => 'Earl',
        'last_name' => 'Hebner',
    ]);
    $originalId = $referee->id;
    $originalCreatedAt = $referee->created_at;

    $referee->delete(); // Soft delete

    RestoreAction::run($referee);

    $referee->refresh();

    // All original data should be preserved
    expect($referee->id)->toBe($originalId);
    expect($referee->first_name)->toBe('Earl');
    expect($referee->last_name)->toBe('Hebner');
    expect($referee->created_at->timestamp)->toBe($originalCreatedAt->timestamp);
    expect($referee->deleted_at)->toBeNull();
});

test('it does not automatically restore employment relationships', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    // End employment and soft delete referee
    $employment->update(['ended_at' => now()]);
    $referee->delete();

    expect($referee->trashed())->toBeTrue();
    expect($employment->fresh()->ended_at)->not->toBeNull();

    RestoreAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    // Referee should be restored but employment should remain ended
    expect($referee->trashed())->toBeFalse();
    expect($employment->ended_at)->not->toBeNull();
    expect($referee->isEmployed())->toBeFalse();
});

test('it preserves historical relationships', function () {
    $referee = Referee::factory()->create();

    // Create some historical relationships
    $referee->employments()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(15),
    ]);

    $referee->injuries()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(5),
    ]);

    $referee->delete(); // Soft delete

    RestoreAction::run($referee);

    $referee->refresh();

    // Historical relationships should be preserved
    expect($referee->employments()->count())->toBe(1);
    expect($referee->injuries()->count())->toBe(1);

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
    ]);

    $this->assertDatabaseHas('referees_injuries', [
        'referee_id' => $referee->id,
    ]);
});

test('it allows referee to be re-employed after restoration', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    // End employment and delete referee
    $employment->update(['ended_at' => now()]);
    $referee->delete();

    RestoreAction::run($referee);

    $referee->refresh();
    expect($referee->trashed())->toBeFalse();
    expect($referee->isEmployed())->toBeFalse();

    // Should be able to employ the restored referee
    $referee->employments()->create([
        'started_at' => now(),
        'ended_at' => null,
    ]);
    $referee->update(['status' => EmploymentStatus::Employed]);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();
});
