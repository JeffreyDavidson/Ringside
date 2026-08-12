<?php

declare(strict_types=1);

use App\Actions\Titles\RetireAction;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

test('it closes current title activity when scheduling retirement', function () {
    testTime()->freeze();

    $title = Title::factory()->active()->create();
    $retirementDate = now()->startOfSecond()->addMonth();

    resolve(RetireAction::class)->handle($title, $retirementDate);

    expect($title->currentActivityPeriod()->doesntExist())->toBeTrue()
        ->and($title->activityPeriods()->latest('id')->firstOrFail()->ended_at)->toEqual(now()->startOfSecond())
        ->and($title->currentRetirement()->firstOrFail()->started_at)->toEqual($retirementDate);
});
