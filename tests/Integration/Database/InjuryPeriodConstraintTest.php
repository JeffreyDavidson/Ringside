<?php

declare(strict_types=1);

use App\Models\Lifecycle\Injury;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

test('an injurable has only one open injury period', function (string $modelClass) {
    /** @var Model $injurable */
    $injurable = $modelClass::factory()->create();

    Injury::factory()->count(2)->for($injurable, 'injurable')->create(['ended_at' => now()]);
    Injury::factory()->for($injurable, 'injurable')->create(['ended_at' => null]);

    expect(fn () => Injury::factory()
        ->for($injurable, 'injurable')
        ->create(['ended_at' => null]))
        ->toThrow(QueryException::class);
})->with([Wrestler::class, Manager::class, Referee::class]);
