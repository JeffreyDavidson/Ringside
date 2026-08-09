<?php

declare(strict_types=1);

use App\Lifecycle\RetirementPeriodManager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts a retirement period on the effective date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDay();

    resolve(RetirementPeriodManager::class)->start($wrestler, $effectiveDate);

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it ends and preserves the active retirement period', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $effectiveDate = now()->subHour();
    $retirementId = $wrestler->currentRetirement()->firstOrFail()->id;

    resolve(RetirementPeriodManager::class)->end($wrestler, $effectiveDate);

    $this->assertDatabaseHas('wrestlers_retirements', [
        'id' => $retirementId,
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});
