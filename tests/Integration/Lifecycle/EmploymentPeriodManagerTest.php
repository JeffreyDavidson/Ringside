<?php

declare(strict_types=1);

use App\Lifecycle\EmploymentPeriodManager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts an employment period on the effective date', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();
    $effectiveDate = now()->subDay();

    resolve(EmploymentPeriodManager::class)->start($wrestler, $effectiveDate);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it ends and preserves the active employment period', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subHour();
    $employmentId = $wrestler->currentEmployment()->firstOrFail()->id;

    resolve(EmploymentPeriodManager::class)->end($wrestler, $effectiveDate);

    $this->assertDatabaseHas('employments', [
        'id' => $employmentId,
        'employable_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});
