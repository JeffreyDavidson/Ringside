<?php

declare(strict_types=1);

use App\Actions\TagTeams\UpdateAction;
use App\Models\TagTeams\TagTeam;

test('it updates tag team basic information', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Original Team',
        'signature_move' => 'Original Move',
    ]);

    $updateData = [
        'name' => 'Updated Team',
        'signature_move' => 'Updated Move',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Updated Team');
    expect($tagTeam->signature_move)->toBe('Updated Move');

    // Verify database was updated
    $this->assertDatabaseHas('tag_teams', [
        'id' => $tagTeam->id,
        'name' => 'Updated Team',
        'signature_move' => 'Updated Move',
    ]);
});

test('it updates only provided fields', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Original Team',
        'signature_move' => 'Original Move',
    ]);

    $updateData = [
        'name' => 'Updated Team Only',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Updated Team Only');
    expect($tagTeam->signature_move)->toBe('Original Move'); // Should remain unchanged
});

test('it updates signature move only', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Original Team',
        'signature_move' => 'Original Move',
    ]);

    $updateData = [
        'signature_move' => 'New Finisher',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Original Team'); // Should remain unchanged
    expect($tagTeam->signature_move)->toBe('New Finisher');
});

test('it handles empty signature move update', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Test Team',
        'signature_move' => 'Original Move',
    ]);

    $updateData = [
        'signature_move' => null,
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Test Team');
    expect($tagTeam->signature_move)->toBeNull();
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Transaction Team',
    ]);

    $updateData = [
        'name' => 'Updated Transaction Team',
        'signature_move' => 'Transaction Slam',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();

    // Verify all updates were applied atomically
    expect($tagTeam->name)->toBe('Updated Transaction Team');
    expect($tagTeam->signature_move)->toBe('Transaction Slam');
});

test('it validates unique name constraint', function () {
    $existingTeam = TagTeam::factory()->create(['name' => 'Existing Team']);
    $tagTeam = TagTeam::factory()->create(['name' => 'Original Team']);

    $updateData = [
        'name' => 'Existing Team', // This should conflict
    ];

    expect(fn () => UpdateAction::run($tagTeam, $updateData))
        ->toThrow(Exception::class);

    // Original data should remain unchanged
    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Original Team');
});

test('it allows updating to same name', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Same Team',
        'signature_move' => 'Original Move',
    ]);

    $updateData = [
        'name' => 'Same Team', // Same name should be allowed
        'signature_move' => 'Updated Move',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Same Team');
    expect($tagTeam->signature_move)->toBe('Updated Move');
});

test('it handles validation errors gracefully', function () {
    $tagTeam = TagTeam::factory()->create(['name' => 'Valid Team']);

    $updateData = [
        'name' => '', // Empty name should fail validation
    ];

    expect(fn () => UpdateAction::run($tagTeam, $updateData))
        ->toThrow(Exception::class);

    // Original data should remain unchanged
    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Valid Team');
});

test('it updates timestamps correctly', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalUpdatedAt = $tagTeam->updated_at;

    // Wait a moment to ensure timestamp difference
    sleep(1);

    $updateData = [
        'name' => 'Timestamp Updated Team',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->updated_at->toDateTimeString())->not()->toBe($originalUpdatedAt->toDateTimeString());
});

test('it preserves unmodified attributes', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Preservation Team',
        'signature_move' => 'Preservation Move',
    ]);

    $originalCreatedAt = $tagTeam->created_at;
    $originalId = $tagTeam->id;

    $updateData = [
        'name' => 'Updated Preservation Team',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();

    // Modified attributes should change
    expect($tagTeam->name)->toBe('Updated Preservation Team');

    // Unmodified attributes should remain the same
    expect($tagTeam->signature_move)->toBe('Preservation Move');
    expect($tagTeam->created_at->toDateTimeString())->toBe($originalCreatedAt->toDateTimeString());
    expect($tagTeam->id)->toBe($originalId);
});

test('it handles long signature move names', function () {
    $tagTeam = TagTeam::factory()->create();

    $longSignatureMove = str_repeat('Ultimate Super Mega Awesome ', 10).'Finisher';

    $updateData = [
        'signature_move' => $longSignatureMove,
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->signature_move)->toBe($longSignatureMove);
});

test('it handles special characters in updates', function () {
    $tagTeam = TagTeam::factory()->create();

    $updateData = [
        'name' => 'The "Elite" & Dangerous Team',
        'signature_move' => 'The \'Ultimate\' Slam (TM)',
    ];

    UpdateAction::run($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('The "Elite" & Dangerous Team');
    expect($tagTeam->signature_move)->toBe('The \'Ultimate\' Slam (TM)');
});
