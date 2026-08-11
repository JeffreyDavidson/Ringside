<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\Managers\ManagerInjury;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeInjury;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerInjury;
use Illuminate\Database\QueryException;

test('a wrestler has only one open injury period', function () {
    $wrestler = Wrestler::factory()->create();

    WrestlerInjury::factory()->count(2)->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => now(),
    ]);
    WrestlerInjury::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);

    expect(fn () => WrestlerInjury::factory()->create([
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a manager has only one open injury period', function () {
    $manager = Manager::factory()->create();

    ManagerInjury::factory()->count(2)->create([
        'manager_id' => $manager->id,
        'ended_at' => now(),
    ]);
    ManagerInjury::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);

    expect(fn () => ManagerInjury::factory()->create([
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});

test('a referee has only one open injury period', function () {
    $referee = Referee::factory()->create();

    RefereeInjury::factory()->count(2)->create([
        'referee_id' => $referee->id,
        'ended_at' => now(),
    ]);
    RefereeInjury::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);

    expect(fn () => RefereeInjury::factory()->create([
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]))->toThrow(QueryException::class);
});
