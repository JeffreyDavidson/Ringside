<?php

declare(strict_types=1);

use App\Actions\TagTeams\CreateAction;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('it creates a new tag team', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = [
        'name' => 'The Test Team',
        'signature_move' => 'Double Suplex',
        'wrestler_a_id' => $wrestlerA->id,
        'wrestler_b_id' => $wrestlerB->id,
    ];

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

    $data = [
        'name' => 'Minimal Team',
        'wrestler_a_id' => $wrestlerA->id,
        'wrestler_b_id' => $wrestlerB->id,
    ];

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

    $data = [
        'name' => 'Partnership Team',
        'wrestler_a_id' => $wrestlerA->id,
        'wrestler_b_id' => $wrestlerB->id,
    ];

    $tagTeam = CreateAction::run($data);

    // Verify partnerships were created
    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestlerA->id,
        'ended_at' => null,
    ]);

    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestlerB->id,
        'ended_at' => null,
    ]);

    expect($tagTeam->wrestlers()->count())->toBe(2);
});

test('it handles database transactions correctly', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = [
        'name' => 'Transaction Team',
        'wrestler_a_id' => $wrestlerA->id,
        'wrestler_b_id' => $wrestlerB->id,
    ];

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

    $data = [
        'name' => 'Invalid Team',
        'wrestler_a_id' => $wrestler->id,
        'wrestler_b_id' => $wrestler->id,
    ];

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it prevents creating tag team with invalid wrestlers', function () {
    $data = [
        'name' => 'Invalid Team',
        'wrestler_a_id' => 999,
        'wrestler_b_id' => 998,
    ];

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it validates required fields', function () {
    $data = [
        'signature_move' => 'Test Move',
        // Missing name and wrestlers
    ];

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it creates tag team with all optional fields', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = [
        'name' => 'Full Data Team',
        'signature_move' => 'Ultimate Finisher',
        'wrestler_a_id' => $wrestlerA->id,
        'wrestler_b_id' => $wrestlerB->id,
    ];

    $tagTeam = CreateAction::run($data);

    expect($tagTeam->name)->toBe('Full Data Team');
    expect($tagTeam->signature_move)->toBe('Ultimate Finisher');
    expect($tagTeam->wrestlers()->count())->toBe(2);
});

test('it handles unique name validation', function () {
    $existingTeam = TagTeam::factory()->create(['name' => 'Unique Team']);

    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = [
        'name' => 'Unique Team',
        'wrestler_a_id' => $wrestlerA->id,
        'wrestler_b_id' => $wrestlerB->id,
    ];

    expect(fn () => CreateAction::run($data))
        ->toThrow(Exception::class);
});

test('it creates partnerships with correct timestamps', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = [
        'name' => 'Timestamp Team',
        'wrestler_a_id' => $wrestlerA->id,
        'wrestler_b_id' => $wrestlerB->id,
    ];

    $tagTeam = CreateAction::run($data);

    // Check partnerships have current timestamp
    $partnerships = $tagTeam->wrestlers()->get();
    foreach ($partnerships as $wrestler) {
        expect($wrestler->pivot->started_at)->not()->toBeNull();
        expect($wrestler->pivot->ended_at)->toBeNull();
    }
});
