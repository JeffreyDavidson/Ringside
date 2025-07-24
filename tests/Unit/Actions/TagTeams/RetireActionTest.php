<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires a bookable tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    resolve(RetireAction::class)->handle($tagTeam);
    
    // Assert the tag team was retired
    $tagTeam->refresh();
    expect($tagTeam->status)->toBe(\App\Enums\Shared\EmploymentStatus::Retired);
    expect($tagTeam->retirements)->toHaveCount(1);
    expect($tagTeam->retirements->first()->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it retires a bookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    resolve(RetireAction::class)->handle($tagTeam, $datetime);
    
    // Assert the tag team was retired at the specific datetime
    $tagTeam->refresh();
    expect($tagTeam->status)->toBe(\App\Enums\Shared\EmploymentStatus::Retired);
    expect($tagTeam->retirements)->toHaveCount(1);
    expect($tagTeam->retirements->first()->started_at->toDateTimeString())->toBe($datetime->toDateTimeString());
});

test('it retires a released tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->released()->create();

    resolve(RetireAction::class)->handle($tagTeam);
    
    // Assert the tag team was retired
    $tagTeam->refresh();
    expect($tagTeam->status)->toBe(\App\Enums\Shared\EmploymentStatus::Retired);
    expect($tagTeam->retirements)->toHaveCount(1);
    expect($tagTeam->retirements->first()->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it retires a released tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->released()->create();
    $datetime = now()->addDays(2);

    resolve(RetireAction::class)->handle($tagTeam, $datetime);
    
    // Assert the tag team was retired at the specific datetime
    $tagTeam->refresh();
    expect($tagTeam->status)->toBe(\App\Enums\Shared\EmploymentStatus::Retired);
    expect($tagTeam->retirements)->toHaveCount(1);
    expect($tagTeam->retirements->first()->started_at->toDateTimeString())->toBe($datetime->toDateTimeString());
});

test('it throws exception when trying to retire a suspended tag team due to wrestler suspension', function () {
    // Create bookable tag team and manually suspend it (which suspends wrestlers too)
    $tagTeam = TagTeam::factory()->bookable()->create();
    \App\Actions\TagTeams\SuspendAction::run($tagTeam);

    // Expect exception because suspended wrestlers prevent tag team retirement
    expect(fn() => resolve(RetireAction::class)->handle($tagTeam))
        ->toThrow(\App\Exceptions\Status\CannotBeRetiredException::class);
});

test('it throws exception when trying to retire a suspended tag team at specific datetime due to wrestler suspension', function () {
    // Create bookable tag team and manually suspend it (which suspends wrestlers too)
    $tagTeam = TagTeam::factory()->bookable()->create();
    \App\Actions\TagTeams\SuspendAction::run($tagTeam);
    $datetime = now()->addDays(2);

    // Expect exception because suspended wrestlers prevent tag team retirement
    expect(fn() => resolve(RetireAction::class)->handle($tagTeam, $datetime))
        ->toThrow(\App\Exceptions\Status\CannotBeRetiredException::class);
});

test('it retires an employed tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    resolve(RetireAction::class)->handle($tagTeam);
    
    // Assert the tag team was retired
    $tagTeam->refresh();
    expect($tagTeam->status)->toBe(\App\Enums\Shared\EmploymentStatus::Retired);
    expect($tagTeam->retirements)->toHaveCount(1);
    expect($tagTeam->retirements->first()->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it retires an employed tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $datetime = now()->addDays(2);

    resolve(RetireAction::class)->handle($tagTeam, $datetime);
    
    // Assert the tag team was retired at the specific datetime
    $tagTeam->refresh();
    expect($tagTeam->status)->toBe(\App\Enums\Shared\EmploymentStatus::Retired);
    expect($tagTeam->retirements)->toHaveCount(1);
    expect($tagTeam->retirements->first()->started_at->toDateTimeString())->toBe($datetime->toDateTimeString());
});

test('it throws exception for retiring a non retirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($tagTeam);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
