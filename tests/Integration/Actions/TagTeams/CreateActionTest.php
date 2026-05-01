<?php

declare(strict_types=1);

use App\Actions\TagTeams\CreateAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('it creates a new tag team', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'The Test Team',
        signature_move: 'Double Suplex',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = CreateAction::run($data);

    expect($tagTeam)->not()->toBeNull();
    expect($tagTeam->name)->toBe('The Test Team');
    expect($tagTeam->signature_move)->toBe('Double Suplex');

    // Verify tag team was created in database
    $this->assertDatabaseHas('tag_teams', [
        'name' => 'The Test Team',
        'signature_move' => 'Double Suplex',
    ]);
});

test('it creates tag team with minimal data', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Minimal Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = CreateAction::run($data);

    expect($tagTeam)->not()->toBeNull();
    expect($tagTeam->name)->toBe('Minimal Team');
    expect($tagTeam->signature_move)->toBeNull();

    $this->assertDatabaseHas('tag_teams', [
        'name' => 'Minimal Team',
        'signature_move' => null,
    ]);
});

test('it creates partnerships for both wrestlers', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Partnership Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = CreateAction::run($data);

    // Verify partnerships were created
    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestlerA->id,
        'left_at' => null,
    ]);

    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestlerB->id,
        'left_at' => null,
    ]);

    expect($tagTeam->wrestlers()->count())->toBe(2);
});

test('it handles database transactions correctly', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Transaction Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = CreateAction::run($data);

    expect($tagTeam->exists)->toBeTrue();
    expect($tagTeam->wrestlers()->count())->toBe(2);

    // Verify all related records were created atomically
    $wrestlers = $tagTeam->wrestlers;
    expect($wrestlers->contains($wrestlerA))->toBeTrue();
    expect($wrestlers->contains($wrestlerB))->toBeTrue();
});

test('it prevents creating tag team with same wrestler twice', function () {
    $wrestler = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Invalid Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestler,
        wrestlerB: $wrestler,
    );

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it prevents creating tag team with missing wrestlers', function () {
    $data = new TagTeamData(
        name: 'Invalid Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: null,
        wrestlerB: null,
    );

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it validates required name', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: '',
        signature_move: 'Test Move',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it creates tag team with all optional fields', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Full Data Team',
        signature_move: 'Ultimate Finisher',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = CreateAction::run($data);

    expect($tagTeam->name)->toBe('Full Data Team');
    expect($tagTeam->signature_move)->toBe('Ultimate Finisher');
    expect($tagTeam->wrestlers()->count())->toBe(2);
});

test('it handles unique name validation', function () {
    TagTeam::factory()->create(['name' => 'Unique Team']);

    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Unique Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it creates partnerships with correct timestamps', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Timestamp Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = CreateAction::run($data);

    // Check partnerships have current timestamp
    $partnerships = $tagTeam->wrestlers()->get();
    foreach ($partnerships as $wrestler) {
        expect($wrestler->pivot->joined_at)->not()->toBeNull();
        expect($wrestler->pivot->left_at)->toBeNull();
    }
});
