<?php

declare(strict_types=1);

use App\Actions\TagTeams\UpdateAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\TagTeams\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->employed()->create([
        'name' => 'Original Team',
        'signature_move' => 'Original Move',
    ]);

    $wrestlers = $this->tagTeam->wrestlers;
    $this->wrestlerA = $wrestlers->first();
    $this->wrestlerB = $wrestlers->last();
});

test('it updates tag team basic information', function () {
    $updateData = new TagTeamData(
        name: 'Updated Team',
        signature_move: 'Updated Move',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('Updated Team');
    expect($this->tagTeam->signature_move)->toBe('Updated Move');

    $this->assertDatabaseHas('tag_teams', [
        'id' => $this->tagTeam->id,
        'name' => 'Updated Team',
        'signature_move' => 'Updated Move',
    ]);
});

test('it updates only the name when signature move is repeated', function () {
    $updateData = new TagTeamData(
        name: 'Updated Team Only',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('Updated Team Only');
    expect($this->tagTeam->signature_move)->toBe('Original Move');
});

test('it updates only the signature move when name is repeated', function () {
    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: 'New Finisher',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('Original Team');
    expect($this->tagTeam->signature_move)->toBe('New Finisher');
});

test('it handles clearing the signature move', function () {
    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('Original Team');
    expect($this->tagTeam->signature_move)->toBeNull();
});

test('it handles database transactions correctly', function () {
    $updateData = new TagTeamData(
        name: 'Updated Transaction Team',
        signature_move: 'Transaction Slam',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();

    expect($this->tagTeam->name)->toBe('Updated Transaction Team');
    expect($this->tagTeam->signature_move)->toBe('Transaction Slam');
});

test('it validates unique name constraint', function () {
    TagTeam::factory()->create(['name' => 'Existing Team']);

    $updateData = new TagTeamData(
        name: 'Existing Team',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    expect(fn () => UpdateAction::run($this->tagTeam, $updateData))
        ->toThrow(Exception::class);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('Original Team');
});

test('it allows updating to the same name', function () {
    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: 'Updated Move',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('Original Team');
    expect($this->tagTeam->signature_move)->toBe('Updated Move');
});

test('it rejects an empty name', function () {
    $updateData = new TagTeamData(
        name: '',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    expect(fn () => UpdateAction::run($this->tagTeam, $updateData))
        ->toThrow(Exception::class);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('Original Team');
});

test('it updates timestamps correctly', function () {
    $originalUpdatedAt = $this->tagTeam->updated_at;

    sleep(1);

    $updateData = new TagTeamData(
        name: 'Timestamp Updated Team',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->updated_at->toDateTimeString())->not()->toBe($originalUpdatedAt->toDateTimeString());
});

test('it preserves unmodified attributes', function () {
    $originalCreatedAt = $this->tagTeam->created_at;
    $originalId = $this->tagTeam->id;

    $updateData = new TagTeamData(
        name: 'Updated Preservation Team',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();

    expect($this->tagTeam->name)->toBe('Updated Preservation Team');
    expect($this->tagTeam->signature_move)->toBe('Original Move');
    expect($this->tagTeam->created_at->toDateTimeString())->toBe($originalCreatedAt->toDateTimeString());
    expect($this->tagTeam->id)->toBe($originalId);
});

test('it handles long signature move names', function () {
    $longSignatureMove = str_repeat('Ultimate Super ', 5).'Finisher';

    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: $longSignatureMove,
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->signature_move)->toBe($longSignatureMove);
});

test('it handles special characters in updates', function () {
    $updateData = new TagTeamData(
        name: 'The "Elite" & Dangerous Team',
        signature_move: 'The \'Ultimate\' Slam (TM)',
        employment_date: null,
        wrestlerA: $this->wrestlerA,
        wrestlerB: $this->wrestlerB,
    );

    UpdateAction::run($this->tagTeam, $updateData);

    $this->tagTeam->refresh();
    expect($this->tagTeam->name)->toBe('The "Elite" & Dangerous Team');
    expect($this->tagTeam->signature_move)->toBe('The \'Ultimate\' Slam (TM)');
});
