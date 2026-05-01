<?php

declare(strict_types=1);

use App\Actions\TagTeams\RestoreAction;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('it restores a soft-deleted tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalName = $tagTeam->name;

    // First delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();
    expect($tagTeam->name)->toBe($originalName);

    // Verify restoration in database
    $this->assertDatabaseHas('tag_teams', [
        'id' => $tagTeam->id,
        'name' => $originalName,
        'deleted_at' => null,
    ]);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalId = $tagTeam->id;

    // Delete first
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify restoration was successful
    expect($tagTeam->trashed())->toBeFalse();
    expect($tagTeam->id)->toBe($originalId);
    expect($tagTeam->exists)->toBeTrue();
});

test('it prevents restoring non-deleted tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->trashed())->toBeFalse();

    expect(fn () => RestoreAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it restores tag team with historical data intact', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create some historical data
    $tagTeam->employments()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(15),
    ]);

    $tagTeam->retirements()->create([
        'started_at' => now()->subDays(15),
        'ended_at' => now()->subDays(10),
    ]);

    expect($tagTeam->employments()->count())->toBe(1);
    expect($tagTeam->retirements()->count())->toBe(1);

    // Delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();

    // Historical data should be preserved
    expect($tagTeam->employments()->count())->toBe(1);
    expect($tagTeam->retirements()->count())->toBe(1);
});

test('it restores tag team with partnership history', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();

    // Create partnership history
    $tagTeam->wrestlers()->attach($wrestler->id, [
        'joined_at' => now()->subDays(10),
        'left_at' => now()->subDays(5),
    ]);

    expect($tagTeam->wrestlers()->count())->toBe(1);

    // Delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();

    // Partnership history should be preserved
    expect($tagTeam->wrestlers()->count())->toBe(1);
});

test('it restores tag team to unemployed state', function () {
    $tagTeam = TagTeam::factory()->create();

    // Ensure tag team starts unemployed
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    // Delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();

    // Should remain in unemployed state
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();
});

test('it handles restoration with complex historical status', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create complex status history (all ended)
    $tagTeam->employments()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(25),
    ]);

    $tagTeam->retirements()->create([
        'started_at' => now()->subDays(25),
        'ended_at' => now()->subDays(20),
    ]);

    $tagTeam->employments()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(15),
    ]);

    // Delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();

    // All historical records should be preserved
    expect($tagTeam->employments()->count())->toBe(2);
    expect($tagTeam->retirements()->count())->toBe(1);

    // Should be in unemployed state (no active records)
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeFalse();
});

test('it restores all tag team attributes correctly', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'The Restoration Team',
        'signature_move' => 'Restoration Slam',
    ]);

    $originalAttributes = $tagTeam->getAttributes();

    // Delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();

    // All original attributes should be preserved
    expect($tagTeam->name)->toBe('The Restoration Team');
    expect($tagTeam->signature_move)->toBe('Restoration Slam');
});

test('it handles concurrent restoration attempts gracefully', function () {
    $tagTeam = TagTeam::factory()->create();

    // Delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    // First restoration should succeed
    RestoreAction::run($tagTeam);
    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();

    // Second restoration attempt should fail
    expect(fn () => RestoreAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it maintains data integrity during restoration', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalCreatedAt = $tagTeam->created_at;
    $originalUpdatedAt = $tagTeam->updated_at;

    // Delete the tag team
    $tagTeam->delete();
    expect($tagTeam->trashed())->toBeTrue();

    RestoreAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeFalse();

    // Timestamps should be preserved (creation) but updated (modification)
    expect($tagTeam->created_at->toDateTimeString())->toBe($originalCreatedAt->toDateTimeString());
    expect($tagTeam->deleted_at)->toBeNull();
});
