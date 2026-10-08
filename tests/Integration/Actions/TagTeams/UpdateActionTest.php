<?php

declare(strict_types=1);

use App\Actions\TagTeams\UpdateAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\travel;

/**
 * @return array{
 *     tagTeam: TagTeam,
 *     wrestlerA: Wrestler,
 *     wrestlerB: Wrestler,
 * }
 */
function tagTeamsUpdateActionFixtures(): array
{
    $tagTeam = TagTeam::factory()->employed()->create([
        'name' => 'Original Team',
        'signature_move' => 'Original Move',
    ]);

    $wrestlers = $tagTeam->wrestlers;
    $wrestlerA = $wrestlers->firstOrFail();
    $wrestlerB = $wrestlers->reverse()->firstOrFail();

    return [
        'tagTeam' => $tagTeam,
        'wrestlerA' => $wrestlerA,
        'wrestlerB' => $wrestlerB,
    ];
}

test('it updates tag team basic information', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $updateData = new TagTeamData(
        name: 'Updated Team',
        signature_move: 'Updated Move',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Updated Team')
        ->and($tagTeam->signature_move)->toBe('Updated Move');

    assertDatabaseHas('tag_teams', [
        'id' => $tagTeam->id,
        'name' => 'Updated Team',
        'signature_move' => 'Updated Move',
    ]);
});

test('it updates using the current persisted tag team state', function () {
    ['tagTeam' => $tagTeam, 'wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB] = tagTeamsUpdateActionFixtures();

    $staleTagTeam = $tagTeam->replicate(['id']);
    $staleTagTeam->id = $tagTeam->id;
    $staleTagTeam->exists = true;

    $updateData = new TagTeamData(
        name: 'Updated From Stale Team',
        signature_move: $tagTeam->signature_move,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $updatedTagTeam = resolve(UpdateAction::class)->handle($staleTagTeam, $updateData);

    expect($updatedTagTeam->name)->toBe('Updated From Stale Team')
        ->and(TagTeam::query()->findOrFail($tagTeam->id)->name)->toBe('Updated From Stale Team');
});

test('it updates only the name when signature move is repeated', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $updateData = new TagTeamData(
        name: 'Updated Team Only',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Updated Team Only')
        ->and($tagTeam->signature_move)->toBe('Original Move');
});

test('it updates only the signature move when name is repeated', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: 'New Finisher',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Original Team')
        ->and($tagTeam->signature_move)->toBe('New Finisher');
});

test('it handles clearing the signature move', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Original Team')
        ->and($tagTeam->signature_move)->toBeNull();
});

test('it handles database transactions correctly', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $updateData = new TagTeamData(
        name: 'Updated Transaction Team',
        signature_move: 'Transaction Slam',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();

    expect($tagTeam->name)->toBe('Updated Transaction Team')
        ->and($tagTeam->signature_move)->toBe('Transaction Slam');
});

test('it allows updating to the same name', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: 'Updated Move',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('Original Team')
        ->and($tagTeam->signature_move)->toBe('Updated Move');
});

test('it updates timestamps correctly', function () {
    ['tagTeam' => $tagTeam, 'wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB] = tagTeamsUpdateActionFixtures();

    $originalUpdatedAt = $tagTeam->updated_at;

    travel(1)->second();

    $updateData = new TagTeamData(
        name: 'Timestamp Updated Team',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect(requiredDate($tagTeam->updated_at)->toDateTimeString())->not()->toBe(requiredDate($originalUpdatedAt)->toDateTimeString());
});

test('it preserves unmodified attributes', function () {
    ['tagTeam' => $tagTeam, 'wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB] = tagTeamsUpdateActionFixtures();

    $originalCreatedAt = $tagTeam->created_at;
    $originalId = $tagTeam->id;

    $updateData = new TagTeamData(
        name: 'Updated Preservation Team',
        signature_move: 'Original Move',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();

    expect($tagTeam->name)->toBe('Updated Preservation Team')
        ->and($tagTeam->signature_move)->toBe('Original Move')
        ->and(requiredDate($tagTeam->created_at)->toDateTimeString())->toBe(requiredDate($originalCreatedAt)->toDateTimeString())
        ->and($tagTeam->id)->toBe($originalId);
});

test('it handles long signature move names', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $longSignatureMove = str_repeat('Ultimate Super ', 5).'Finisher';

    $updateData = new TagTeamData(
        name: 'Original Team',
        signature_move: $longSignatureMove,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->signature_move)->toBe($longSignatureMove);
});

test('it handles special characters in updates', function () {
    ['wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB, 'tagTeam' => $tagTeam] = tagTeamsUpdateActionFixtures();

    $updateData = new TagTeamData(
        name: 'The "Elite" & Dangerous Team',
        signature_move: 'The \'Ultimate\' Slam (TM)',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    resolve(UpdateAction::class)->handle($tagTeam, $updateData);

    $tagTeam->refresh();
    expect($tagTeam->name)->toBe('The "Elite" & Dangerous Team')
        ->and($tagTeam->signature_move)->toBe('The \'Ultimate\' Slam (TM)');
});

test('it employs newly assigned members when the tag team is employed', function () {
    ['tagTeam' => $tagTeam, 'wrestlerA' => $wrestlerA, 'wrestlerB' => $wrestlerB] = tagTeamsUpdateActionFixtures();

    $newWrestler = Wrestler::factory()->create();
    $newManager = Manager::factory()->create();

    resolve(UpdateAction::class)->handle($tagTeam, new TagTeamData(
        name: $tagTeam->name,
        signature_move: $tagTeam->signature_move,
        employment_date: now(),
        wrestlerA: $wrestlerA,
        wrestlerB: $newWrestler,
        managers: new Collection([$newManager]),
    ));

    expect($newWrestler->currentEmployment()->exists())->toBeTrue()
        ->and($newManager->currentEmployment()->exists())->toBeTrue()
        ->and($tagTeam->currentWrestlers()->whereKey($wrestlerB->id)->exists())->toBeFalse()
        ->and($tagTeam->previousWrestlers()->whereKey($wrestlerB->id)->exists())->toBeTrue();
});

test('it employs a tag team that has never been employed when an employment date is given', function () {
    tagTeamsUpdateActionFixtures();

    $tagTeam = TagTeam::factory()->unemployed()->create();
    [$wrestlerA, $wrestlerB] = $tagTeam->currentWrestlers()->get()->all();
    $employmentDate = now()->subWeek()->startOfSecond();

    $updatedTagTeam = resolve(UpdateAction::class)->handle($tagTeam, new TagTeamData(
        name: $tagTeam->name,
        signature_move: $tagTeam->signature_move,
        employment_date: $employmentDate,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    ));

    expect($updatedTagTeam->currentEmployment()->sole()->started_at->toDateTimeString())
        ->toBe($employmentDate->toDateTimeString())
        ->and($wrestlerA->currentEmployment()->exists())->toBeTrue()
        ->and($wrestlerB->currentEmployment()->exists())->toBeTrue();
});

test('it does not employ a tag team when no employment date is given', function () {
    tagTeamsUpdateActionFixtures();

    $tagTeam = TagTeam::factory()->unemployed()->create();
    [$wrestlerA, $wrestlerB] = $tagTeam->currentWrestlers()->get()->all();

    $updatedTagTeam = resolve(UpdateAction::class)->handle($tagTeam, new TagTeamData(
        name: 'Renamed Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    ));

    expect($updatedTagTeam->name)->toBe('Renamed Team')
        ->and($updatedTagTeam->employments()->exists())->toBeFalse()
        ->and($wrestlerA->employments()->exists())->toBeFalse();
});
