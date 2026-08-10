<?php

declare(strict_types=1);

use App\Actions\Stables\MergeStablesAction;
use App\Models\Stables\Stable;

it('moves current members to the primary stable and preserves secondary history', function () {
    $primaryStable = Stable::factory()->active()->create();
    $secondaryStable = Stable::factory()->active()->create();
    $primaryWrestlerIds = $primaryStable->currentWrestlers()->pluck('wrestlers.id');
    $primaryTagTeamIds = $primaryStable->currentTagTeams()->pluck('tag_teams.id');
    $secondaryWrestlers = $secondaryStable->currentWrestlers()->get();
    $secondaryTagTeams = $secondaryStable->currentTagTeams()->get();
    $mergeDate = now();

    resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        $mergeDate,
    );

    expect($primaryStable->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($primaryWrestlerIds->merge($secondaryWrestlers->modelKeys())->all())
        ->and($primaryStable->currentTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($primaryTagTeamIds->merge($secondaryTagTeams->modelKeys())->all())
        ->and($secondaryStable->currentWrestlers()->exists())
        ->toBeFalse()
        ->and($secondaryStable->currentTagTeams()->exists())
        ->toBeFalse()
        ->and($secondaryStable->previousWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($secondaryWrestlers->modelKeys())
        ->and($secondaryStable->previousTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($secondaryTagTeams->modelKeys());

    $this->assertSoftDeleted($secondaryStable);
});
