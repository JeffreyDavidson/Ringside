<?php

declare(strict_types=1);

use App\Actions\TagTeams\CreateAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

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

    $tagTeam = resolve(CreateAction::class)->handle($data);

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

    $tagTeam = resolve(CreateAction::class)->handle($data);

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

    $tagTeam = resolve(CreateAction::class)->handle($data);

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

    $tagTeam = resolve(CreateAction::class)->handle($data);

    expect($tagTeam->exists)->toBeTrue();
    expect($tagTeam->wrestlers()->count())->toBe(2);

    // Verify all related records were created atomically
    $wrestlers = $tagTeam->wrestlers;
    expect($wrestlers->contains($wrestlerA))->toBeTrue();
    expect($wrestlers->contains($wrestlerB))->toBeTrue();
});

test('it receives validated wrestlers in its data', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Invalid Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    expect($data->wrestlerA)->toBe($wrestlerA)
        ->and($data->wrestlerB)->toBe($wrestlerB);
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

    $tagTeam = resolve(CreateAction::class)->handle($data);

    expect($tagTeam->name)->toBe('Full Data Team');
    expect($tagTeam->signature_move)->toBe('Ultimate Finisher');
    expect($tagTeam->wrestlers()->count())->toBe(2);
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

    $tagTeam = resolve(CreateAction::class)->handle($data);

    // Check partnerships have current timestamp
    $partnerships = $tagTeam->wrestlers()->get();
    foreach ($partnerships as $wrestler) {
        $membership = TagTeamWrestler::query()
            ->whereBelongsTo($tagTeam, 'tagTeam')
            ->whereBelongsTo($wrestler)
            ->firstOrFail();

        expect($membership->joined_at)->not->toBeNull();
        expect($membership->left_at)->toBeNull();
    }
});

test('it employs the tag team and its founding members', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    $employmentDate = now()->subDay();

    $tagTeam = resolve(CreateAction::class)->handle(new TagTeamData(
        name: 'Employed Team',
        signature_move: null,
        employment_date: $employmentDate,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
        managers: new Collection([$manager]),
    ));

    expect($tagTeam->isEmployed())->toBeTrue()
        ->and($wrestlerA->isEmployed())->toBeTrue()
        ->and($wrestlerB->isEmployed())->toBeTrue()
        ->and($manager->isEmployed())->toBeTrue();
});
