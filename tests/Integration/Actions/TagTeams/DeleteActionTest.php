<?php

declare(strict_types=1);

use App\Actions\TagTeams\DeleteAction;
use App\Exceptions\Roster\TagTeams\CannotBeDeletedException;
use App\Lifecycle\Roster\TagTeams\TagTeamDeletionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('it soft deletes a tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->trashed())->toBeFalse()
        ->and(resolve(TagTeamDeletionEligibility::class)->canDelete($tagTeam))->toBeTrue();

    resolve(DeleteAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue()
        ->and(resolve(TagTeamDeletionEligibility::class)->canDelete($tagTeam))->toBeFalse();

    // Verify soft delete in database
    $this->assertSoftDeleted('tag_teams', [
        'id' => $tagTeam->id,
    ]);
});

test('it deletes unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->currentEmployment()->exists())->toBeFalse();

    resolve(DeleteAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue();
});

test('it prevents deleting employed tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->currentEmployment()->exists())->toBeTrue();

    expect(fn () => resolve(DeleteAction::class)->handle($tagTeam))
        ->toThrow(CannotBeDeletedException::class);
});

test('it prevents deleting retired tag team', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    expect($tagTeam->currentRetirement()->exists())->toBeTrue()
        ->and(resolve(TagTeamDeletionEligibility::class)->canDelete($tagTeam))->toBeFalse();

    expect(fn () => resolve(DeleteAction::class)->handle($tagTeam))
        ->toThrow(CannotBeDeletedException::class);
});

test('it prevents deleting suspended tag team', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    expect($tagTeam->isSuspended())->toBeTrue();

    expect(fn () => resolve(DeleteAction::class)->handle($tagTeam))
        ->toThrow(CannotBeDeletedException::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalId = $tagTeam->id;

    resolve(DeleteAction::class)->handle($tagTeam);

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
        'joined_at' => now()->subDays(5),
        'left_at' => null,
    ]);

    $tagTeam->wrestlers()->attach($wrestlerB->id, [
        'joined_at' => now()->subDays(3),
        'left_at' => null,
    ]);

    expect($tagTeam->wrestlers()->count())->toBe(2);

    resolve(DeleteAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue();
});

test('it preserves historical data during deletion', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalName = $tagTeam->name;

    // Create some historical employment data
    $tagTeam->employments()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(15),
    ]);

    resolve(DeleteAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->trashed())->toBeTrue();
    expect($tagTeam->name)->toBe($originalName);

    // Historical data should remain
    expect($tagTeam->employments()->count())->toBe(1);
});

test('it prevents deleting already deleted tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    // Delete the tag team first
    resolve(DeleteAction::class)->handle($tagTeam);
    expect($tagTeam->trashed())->toBeTrue();

    // Attempting to delete again should fail
    expect(fn () => resolve(DeleteAction::class)->handle($tagTeam))
        ->toThrow(CannotBeDeletedException::class);
});

test('it uses appropriate business rules for deletion', function () {
    $tagTeam = TagTeam::factory()->create();

    // Tag team should be in a state that allows deletion
    expect($tagTeam->currentEmployment()->exists())->toBeFalse();
    expect($tagTeam->currentRetirement()->exists())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    resolve(DeleteAction::class)->handle($tagTeam);

    expect($tagTeam->trashed())->toBeTrue();
});

test('it handles tag team with no active relationships', function () {
    $tagTeam = TagTeam::factory()->create();

    // Ensure no active relationships
    expect($tagTeam->wrestlers()->count())->toBe(0);

    resolve(DeleteAction::class)->handle($tagTeam);

    expect($tagTeam->trashed())->toBeTrue();
});

test('it handles tag team with ended relationships', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();

    // Add ended partnership
    $tagTeam->wrestlers()->attach($wrestler->id, [
        'joined_at' => now()->subDays(10),
        'left_at' => now()->subDays(5),
    ]);

    resolve(DeleteAction::class)->handle($tagTeam);

    expect($tagTeam->trashed())->toBeTrue();

    // Historical partnership should remain
    expect($tagTeam->wrestlers()->count())->toBe(1);
});
