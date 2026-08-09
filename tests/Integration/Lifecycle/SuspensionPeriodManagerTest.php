<?php

declare(strict_types=1);

use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts a suspension period on the effective date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDay();

    resolve(SuspensionPeriodManager::class)->start($wrestler, $effectiveDate);

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it ends and preserves the active suspension period', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $effectiveDate = now()->subHour();
    $suspensionId = $wrestler->currentSuspension()->firstOrFail()->id;

    resolve(SuspensionPeriodManager::class)->end($wrestler, $effectiveDate);

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'id' => $suspensionId,
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});
