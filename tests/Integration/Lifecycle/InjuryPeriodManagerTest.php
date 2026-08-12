<?php

declare(strict_types=1);

use App\Lifecycle\InjuryPeriodManager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts an injury period on the effective date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDay();

    resolve(InjuryPeriodManager::class)->start($wrestler, $effectiveDate);

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it ends and preserves the active injury period', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $effectiveDate = now()->subHour();
    $injuryId = $wrestler->currentInjury()->firstOrFail()->id;

    resolve(InjuryPeriodManager::class)->end($wrestler, $effectiveDate);

    $this->assertDatabaseHas('injuries', [
        'id' => $injuryId,
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});
