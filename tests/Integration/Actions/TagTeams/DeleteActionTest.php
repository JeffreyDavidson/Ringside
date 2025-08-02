<?php

declare(strict_types=1);

use App\Actions\TagTeams\DeleteAction;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('it soft deletes a tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->trashed())->toBeFalse();

    DeleteAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue();

    // Verify soft delete in database
    $this->assertSoftDeleted('tag_teams', [
        'id' => $tagTeam->id,
    ]);
});

test('it deletes unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->isEmployed())->toBeFalse();

    DeleteAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue();
});

test('it prevents deleting employed tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isEmployed())->toBeTrue();

    expect(fn () => DeleteAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents deleting retired tag team', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    expect($tagTeam->isRetired())->toBeTrue();

    expect(fn () => DeleteAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents deleting suspended tag team', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    expect($tagTeam->isSuspended())->toBeTrue();

    expect(fn () => DeleteAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalId = $tagTeam->id;

    DeleteAction::run($tagTeam);

    // Verify deletion was successful
    expect($tagTeam->trashed())->toBeTrue();

    // Verify record still exists but is soft deleted
    $this->assertSoftDeleted('tag_teams', [
        'id' => $originalId,
    ]);
});

test('it handles cascade deletion of relationships', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create some relationships that should be handled by cascade
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $tagTeam->wrestlers()->attach($wrestlerA->id, [
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    $tagTeam->wrestlers()->attach($wrestlerB->id, [
        'started_at' => now()->subDays(3),
        'ended_at' => null,
    ]);

    expect($tagTeam->wrestlers()->count())->toBe(2);

    DeleteAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue();

    // Note: Cascade behavior would be tested in cascade strategy tests
    // This tests that the action completes successfully with relationships
});

test('it preserves historical data during deletion', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalName = $tagTeam->name;

    // Create some historical employment data
    $tagTeam->employments()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(15),
    ]);

    DeleteAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue();
    expect($tagTeam->name)->toBe($originalName);

    // Historical data should remain
    expect($tagTeam->employments()->count())->toBe(1);
});

test('it prevents deleting already deleted tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    // Delete the tag team first
    DeleteAction::run($tagTeam);
    expect($tagTeam->trashed())->toBeTrue();

    // Attempting to delete again should fail
    expect(fn () => DeleteAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it uses appropriate business rules for deletion', function () {
    $tagTeam = TagTeam::factory()->create();

    // Tag team should be in a state that allows deletion
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    DeleteAction::run($tagTeam);

    expect($tagTeam->trashed())->toBeTrue();
});

test('it handles tag team with no active relationships', function () {
    $tagTeam = TagTeam::factory()->create();

    // Ensure no active relationships
    expect($tagTeam->wrestlers()->count())->toBe(0);

    DeleteAction::run($tagTeam);

    expect($tagTeam->trashed())->toBeTrue();
});

test('it handles tag team with ended relationships', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();

    // Add ended partnership
    $tagTeam->wrestlers()->attach($wrestler->id, [
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(5),
    ]);

    DeleteAction::run($tagTeam);

    expect($tagTeam->trashed())->toBeTrue();

    // Historical partnership should remain
    expect($tagTeam->wrestlers()->count())->toBe(1);
});
