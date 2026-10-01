<?php

declare(strict_types=1);

use App\Actions\TagTeams\CreateAction;
use App\Actions\TagTeams\UpdateAction;
use App\Data\TagTeams\TagTeamData;
use App\Exceptions\Roster\TagTeams\CannotBeEstablishedException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;

function tagTeamData(string $name, Wrestler $wrestlerA, Wrestler $wrestlerB): TagTeamData
{
    return new TagTeamData(
        name: $name,
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );
}

test('creating a tag team rejects a wrestler who is on another current tag team', function () {
    // Arrange
    $existingTagTeam = TagTeam::factory()->employed()->create();
    $sharedWrestler = $existingTagTeam->currentWrestlers()->firstOrFail();
    $newPartner = Wrestler::factory()->create();
    $tagTeamCount = TagTeam::query()->count();

    // Act
    $create = fn () => resolve(CreateAction::class)->handle(tagTeamData('Duplicate Team', $sharedWrestler, $newPartner));

    // Assert
    expect($create)->toThrow(
        CannotBeEstablishedException::class,
        "Wrestler '{$sharedWrestler->name}' is already a member of TagTeam '{$existingTagTeam->name}'",
    )
        ->and(TagTeam::query()->count())->toBe($tagTeamCount)
        ->and(TagTeamWrestler::query()->current()->where('wrestler_id', $sharedWrestler->id)->count())->toBe(1)
        ->and($newPartner->tagTeams()->exists())->toBeFalse();
});

test('two sequential creations sharing a wrestler reject the second', function () {
    // Arrange
    [$sharedWrestler, $firstPartner, $secondPartner] = Wrestler::factory()->count(3)->create()->all();
    resolve(CreateAction::class)->handle(tagTeamData('First Team', $sharedWrestler, $firstPartner));

    // Act
    $create = fn () => resolve(CreateAction::class)->handle(tagTeamData('Second Team', $sharedWrestler, $secondPartner));

    // Assert
    expect($create)->toThrow(CannotBeEstablishedException::class)
        ->and(TagTeam::query()->where('name', 'Second Team')->exists())->toBeFalse()
        ->and(TagTeamWrestler::query()->current()->where('wrestler_id', $sharedWrestler->id)->count())->toBe(1);
});

test('a wrestler whose previous membership ended can join a new tag team', function () {
    // Arrange
    $existingTagTeam = TagTeam::factory()->employed()->create();
    $formerMember = $existingTagTeam->currentWrestlers()->firstOrFail();
    $existingTagTeam->wrestlers()->updateExistingPivot($formerMember->id, ['left_at' => now()->subDay()]);
    $newPartner = Wrestler::factory()->create();

    // Act
    $tagTeam = resolve(CreateAction::class)->handle(tagTeamData('Fresh Start', $formerMember, $newPartner));

    // Assert
    expect($tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())->toEqualCanonicalizing([$formerMember->id, $newPartner->id])
        ->and(TagTeamWrestler::query()->where('wrestler_id', $formerMember->id)->count())->toBe(2);
});

test('a wrestler on a soft-deleted tag team with a current membership is rejected', function () {
    // Arrange
    $existingTagTeam = TagTeam::factory()->employed()->create();
    $sharedWrestler = $existingTagTeam->currentWrestlers()->firstOrFail();
    $existingTagTeam->delete();

    // Act
    $create = fn () => resolve(CreateAction::class)->handle(tagTeamData('Duplicate Team', $sharedWrestler, Wrestler::factory()->create()));

    // Assert
    expect($create)->toThrow(CannotBeEstablishedException::class, "TagTeam '{$existingTagTeam->name}'");
});

test('updating a tag team rejects adding a wrestler who is on another current tag team', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->employed()->create();
    $otherTagTeam = TagTeam::factory()->employed()->create();
    $keptWrestler = $tagTeam->currentWrestlers()->firstOrFail();
    $takenWrestler = $otherTagTeam->currentWrestlers()->firstOrFail();
    $membershipsBefore = TagTeamWrestler::query()->orderBy('id')->get(['id', 'tag_team_id', 'wrestler_id', 'left_at'])->toArray();

    // Act
    $update = fn () => resolve(UpdateAction::class)->handle($tagTeam, tagTeamData('Renamed Team', $keptWrestler, $takenWrestler));

    // Assert
    expect($update)->toThrow(CannotBeEstablishedException::class)
        ->and(TagTeamWrestler::query()->orderBy('id')->get(['id', 'tag_team_id', 'wrestler_id', 'left_at'])->toArray())->toBe($membershipsBefore)
        ->and($tagTeam->refresh()->name)->not->toBe('Renamed Team');
});

test('updating a tag team that keeps its own wrestlers succeeds', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->employed()->create();
    [$wrestlerA, $wrestlerB] = $tagTeam->currentWrestlers()->get()->all();

    // Act
    resolve(UpdateAction::class)->handle($tagTeam, tagTeamData('Renamed Team', $wrestlerA, $wrestlerB));

    // Assert
    expect($tagTeam->refresh()->name)->toBe('Renamed Team')
        ->and($tagTeam->currentWrestlers()->count())->toBe(2);
});

test('updating a tag team can replace a wrestler with a free one', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->employed()->create();
    $keptWrestler = $tagTeam->currentWrestlers()->firstOrFail();
    $replacement = Wrestler::factory()->create();

    // Act
    resolve(UpdateAction::class)->handle($tagTeam, tagTeamData($tagTeam->name, $keptWrestler, $replacement));

    // Assert
    expect($tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())->toEqualCanonicalizing([$keptWrestler->id, $replacement->id]);
});

test('it locks incoming wrestlers in ascending id order after the tag team row when updating', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->employed()->create();
    $incoming = Wrestler::factory()->count(2)->create();
    $descending = $incoming->sortByDesc('id')->values();
    [$first, $second] = $descending->all();

    // Act
    $statements = recordStatements(fn () => resolve(UpdateAction::class)->handle(
        $tagTeam,
        tagTeamData($tagTeam->name, $first, $second),
    ));

    // Assert
    $tagTeamLock = collect($statements)->filter(fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "tag_teams"'))->keys()->firstOrFail();
    $wrestlerLock = collect($statements)->filter(fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "wrestlers" where "wrestlers"."id" in'))->keys()->firstOrFail();

    expect($wrestlerLock)->toBeGreaterThan($tagTeamLock)
        ->and($statements[$wrestlerLock]['sql'])->toContain('order by "wrestlers"."id" asc')
        ->and($statements[$wrestlerLock]['sql'])->toContain("in ({$descending->implode('id', ', ')})");
});

test('it locks incoming wrestlers in ascending id order after the tag team row is created', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(2)->create();
    $descending = $wrestlers->sortByDesc('id')->values();
    [$first, $second] = $descending->all();

    // Act
    $statements = recordStatements(fn () => resolve(CreateAction::class)->handle(
        tagTeamData('Lock Order Team', $first, $second),
    ));

    // Assert
    $insert = collect($statements)->filter(fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "tag_teams"'))->keys()->firstOrFail();
    $wrestlerLock = collect($statements)->filter(fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "wrestlers" where "wrestlers"."id" in'))->keys()->firstOrFail();

    expect($wrestlerLock)->toBeGreaterThan($insert)
        ->and($statements[$wrestlerLock]['sql'])->toContain('order by "wrestlers"."id" asc');
});
