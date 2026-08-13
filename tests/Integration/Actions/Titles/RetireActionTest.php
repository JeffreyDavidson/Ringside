<?php

declare(strict_types=1);

use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Exceptions\Titles\CannotBeUnretiredException;
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

test('retirement closes activity opened from a stale inactive snapshot', function () {
    $title = Title::factory()->inactive()->create();
    $staleTitle = freshModel($title);

    resolve(ReinstateAction::class)->handle($title);
    resolve(RetireAction::class)->handle($staleTitle);

    expect($title->currentActivityPeriod()->doesntExist())->toBeTrue()
        ->and($title->currentRetirement()->exists())->toBeTrue();
});

test('reinstatement revalidates after retirement wins the title lock', function () {
    $title = Title::factory()->inactive()->create();
    $staleTitle = freshModel($title);

    resolve(RetireAction::class)->handle($title);

    expect(fn () => resolve(ReinstateAction::class)->handle($staleTitle))
        ->toThrow(CannotBeReinstatedException::class)
        ->and($title->currentActivityPeriod()->doesntExist())->toBeTrue()
        ->and($title->currentRetirement()->exists())->toBeTrue();
});

test('unretirement rejects a deleted title through the typed eligibility boundary', function () {
    $title = Title::factory()->retired()->create();
    $title->delete();

    expect(fn () => resolve(UnretireAction::class)->handle($title))
        ->toThrow(
            CannotBeUnretiredException::class,
            CannotBeUnretiredException::deleted($title)->getMessage(),
        );
});
