<?php

declare(strict_types=1);

use App\Actions\TagTeams\LockIncomingWrestlersAction;
use App\Exceptions\Roster\TagTeams\CannotBeEstablishedException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('it locks the incoming wrestlers in ascending id order', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestlers = Wrestler::factory()->count(2)->create()->sortByDesc('id')->values();

    // Act
    $statements = recordStatements(fn () => resolve(LockIncomingWrestlersAction::class)->handle($tagTeam, $wrestlers));

    // Assert
    $lock = collect($statements)->filter(fn (array $statement): bool => $statement['locked'])->firstOrFail();

    expect($lock['sql'])->toContain('from "wrestlers"')
        ->and($lock['sql'])->toContain('order by "wrestlers"."id" asc');
});

test('it accepts wrestlers who are free or already on the tag team being established', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->employed()->create();
    $members = Wrestler::query()->whereKey($tagTeam->currentWrestlers()->pluck('wrestlers.id'))->get();
    $freeWrestler = Wrestler::factory()->create();

    // Act
    $lock = fn () => resolve(LockIncomingWrestlersAction::class)->handle($tagTeam, $members->push($freeWrestler));

    // Assert
    expect($lock)->not->toThrow(CannotBeEstablishedException::class);
});

test('it rejects a wrestler who is on another current tag team', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $otherTagTeam = TagTeam::factory()->employed()->create();
    $takenWrestler = $otherTagTeam->currentWrestlers()->firstOrFail();

    // Act
    $lock = fn () => resolve(LockIncomingWrestlersAction::class)->handle($tagTeam, Wrestler::query()->whereKey($takenWrestler->id)->get());

    // Assert
    expect($lock)->toThrow(
        CannotBeEstablishedException::class,
        "Wrestler '{$takenWrestler->name}' is already a member of TagTeam '{$otherTagTeam->name}' and cannot join another tag team.",
    );
});
