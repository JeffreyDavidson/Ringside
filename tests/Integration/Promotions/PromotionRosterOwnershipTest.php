<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

function createUnassignedRosterRecord(string $modelClass): Wrestler|Manager|Referee|TagTeam|Stable
{
    return match ($modelClass) {
        Wrestler::class => Wrestler::factory()->create(),
        Manager::class => Manager::factory()->create(),
        Referee::class => Referee::factory()->create(),
        TagTeam::class => TagTeam::factory()->create(),
        Stable::class => Stable::factory()->create(),
        default => throw new InvalidArgumentException("Unsupported roster model: {$modelClass}"),
    };
}

test('roster records can be assigned to a promotion', function (string $modelClass) {
    $promotion = Promotion::factory()->create();

    $rosterRecord = createUnassignedRosterRecord($modelClass);

    expect($rosterRecord->promotion_id)->toBeNull();

    $rosterRecord->promotion()->associate($promotion);
    $rosterRecord->save();

    $rosterRecord->refresh();

    expect($rosterRecord->promotion_id)->toBe($promotion->id);
})->with([
    Wrestler::class,
    Manager::class,
    Referee::class,
    TagTeam::class,
    Stable::class,
]);

test('roster ownership backfill assigns only unowned records', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();

    $unownedRecords = array_map(
        createUnassignedRosterRecord(...),
        [Wrestler::class, Manager::class, Referee::class, TagTeam::class, Stable::class],
    );
    $ownedWrestler = Wrestler::factory()->for($otherPromotion, 'promotion')->create();

    $exitCode = Artisan::call('promotions:backfill-roster-ownership', [
        'promotion' => $promotion->id,
        '--force' => true,
    ]);

    $ownedWrestler->refresh();

    expect($exitCode)->toBe(0)
        ->and($ownedWrestler->promotion_id)->toBe($otherPromotion->id);

    foreach ($unownedRecords as $unownedRecord) {
        $unownedRecord->refresh();

        expect($unownedRecord->promotion_id)->toBe($promotion->id);
    }
});

test('roster ownership backfill requires confirmation unless previewing', function () {
    $promotion = Promotion::factory()->create();
    $wrestler = Wrestler::factory()->create();

    $exitCode = Artisan::call('promotions:backfill-roster-ownership', [
        'promotion' => $promotion->id,
    ]);

    $wrestler->refresh();

    $dryRunExitCode = Artisan::call('promotions:backfill-roster-ownership', [
        'promotion' => $promotion->id,
        '--dry-run' => true,
    ]);

    $wrestler->refresh();

    expect($exitCode)->toBe(1)
        ->and($dryRunExitCode)->toBe(0)
        ->and($wrestler->promotion_id)->toBeNull();
});
