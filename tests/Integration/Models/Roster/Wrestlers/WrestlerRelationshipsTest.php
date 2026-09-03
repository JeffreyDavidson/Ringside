<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

it('defines manager assignment relationships', function () {
    $wrestler = new Wrestler();
    $managers = $wrestler->managers();

    expect($managers)->toBeInstanceOf(BelongsToMany::class)
        ->and($managers->getTable())->toBe((new WrestlerManager())->getTable())
        ->and($managers->getPivotClass())->toBe(WrestlerManager::class)
        ->and($managers->getPivotColumns())->toContain('hired_at', 'fired_at', 'created_at', 'updated_at')
        ->and($wrestler->currentManagers()->toRawSql())->toContain('"fired_at" is null')
        ->and($wrestler->previousManagers()->toRawSql())->toContain('"fired_at" is not null');
});

it('defines stable membership relationships', function () {
    $wrestler = new Wrestler();
    $stables = $wrestler->stables();
    $currentStable = $wrestler->currentStable();

    expect($stables)->toBeInstanceOf(BelongsToMany::class)
        ->and($currentStable)->toBeInstanceOf(HasOneThrough::class)
        ->and($stables->getRelated())->toBeInstanceOf(Stable::class)
        ->and($stables->getTable())->toBe((new StableWrestler())->getTable())
        ->and($stables->getForeignPivotKeyName())->toBe('wrestler_id')
        ->and($stables->getPivotClass())->toBe(StableWrestler::class)
        ->and($stables->getPivotColumns())->toContain('joined_at', 'left_at', 'created_at', 'updated_at')
        ->and($currentStable->toRawSql())->toContain('"stables_wrestlers"."left_at" is null')
        ->and($wrestler->previousStables()->toRawSql())->toContain('"left_at" is not null');
});
