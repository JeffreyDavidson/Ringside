<?php

declare(strict_types=1);

use App\Models\Lifecycle\Employment;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * Return a manager with a controlled employment period for rule tests.
 */
function managerWithEmploymentPeriod(Carbon $startedAt, ?Carbon $endedAt = null): Manager
{
    $employment = Employment::factory()->started($startedAt);

    if ($endedAt !== null) {
        $employment = $employment->ended($endedAt);
    }

    return Manager::factory()->has($employment, 'employments')->create();
}

describe('CanChangeEmploymentDate validation rule', function () {
    test('rejects changing the original employment date while the model is employed', function () {
        $startedAt = today()->subMonth();
        $manager = managerWithEmploymentPeriod($startedAt);
        $message = null;

        new CanChangeEmploymentDate($manager)->validate(
            'employment_date',
            $startedAt->copy()->subDay(),
            validationFailureCallback(function (string $failure) use (&$message): void {
                $message = $failure;
            }),
        );

        expect($message)->toBe('The employment date cannot be changed while Manager is currently employed.');
    });

    test('rejects an invalid employment date value', function () {
        $manager = Manager::factory()->create();
        $message = null;

        new CanChangeEmploymentDate($manager)->validate(
            'started_at',
            [],
            validationFailureCallback(function (string $failure) use (&$message): void {
                $message = $failure;
            }),
        );

        expect($message)->toBe('The employment date must be a valid date.');
    });

    test('passes when the model is not currently employed', function () {
        $manager = Manager::factory()->create();
        $failed = false;

        new CanChangeEmploymentDate($manager)->validate(
            'started_at',
            now()->addWeek(),
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        expect($failed)->toBeFalse();
    });

    test('rejects a date outside the current employment period', function () {
        $manager = managerWithEmploymentPeriod(now()->subMonth());
        $failed = false;

        new CanChangeEmploymentDate($manager)->validate(
            'started_at',
            now()->subMonths(2),
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        expect($failed)->toBeTrue();
    });

    test('passes a date inside the current employment period', function () {
        $manager = managerWithEmploymentPeriod(now()->subMonth());
        $failed = false;

        new CanChangeEmploymentDate($manager)->validate(
            'started_at',
            now()->subWeek(),
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        expect($failed)->toBeFalse();
    });

    test('uses the model name when reporting a blocked date', function () {
        $wrestler = Wrestler::factory()->employed()->create(['name' => 'Test Wrestler']);
        $message = null;

        new CanChangeEmploymentDate($wrestler)->validate(
            'started_at',
            now()->subMonths(2),
            validationFailureCallback(function (string $failure) use (&$message): void {
                $message = $failure;
            }),
        );

        expect($message)->toBe('The employment date cannot be changed while Test Wrestler is currently employed.');
    });

    test('uses the class basename when a model has no name attribute', function () {
        $manager = managerWithEmploymentPeriod(now()->subMonth());
        $message = null;

        new CanChangeEmploymentDate($manager)->validate(
            'started_at',
            now()->subMonths(2),
            validationFailureCallback(function (string $failure) use (&$message): void {
                $message = $failure;
            }),
        );

        expect($message)->toBe('The employment date cannot be changed while Manager is currently employed.');
    });

    test('accepts string date values supported by Carbon', function (string $value) {
        $manager = managerWithEmploymentPeriod(now()->subMonth());
        $failed = false;

        new CanChangeEmploymentDate($manager)->validate(
            'started_at',
            $value,
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        expect($failed)->toBeTrue();
    })->with([
        '2026-06-30',
        '2026-06-30 00:00:00',
    ]);

    test('accepts Carbon date values', function () {
        $manager = managerWithEmploymentPeriod(now()->subMonth());
        $failed = false;

        new CanChangeEmploymentDate($manager)->validate(
            'started_at',
            Carbon::parse('2026-06-30'),
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        expect($failed)->toBeTrue();
    });

    test('passes when no model is provided', function () {
        $failed = false;

        new CanChangeEmploymentDate(null)->validate(
            'started_at',
            now()->addWeek(),
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        expect($failed)->toBeFalse();
    });

    test('implements the validation rule contract', function () {
        expect(new CanChangeEmploymentDate(null))->toBeInstanceOf(ValidationRule::class);
    });

    test('uses the same validation behavior regardless of attribute name', function () {
        $manager = managerWithEmploymentPeriod(now()->subMonth());
        $failed = 0;
        $rule = new CanChangeEmploymentDate($manager);

        foreach (['started_at', 'employment_date', 'hire_date'] as $attribute) {
            $rule->validate(
                $attribute,
                now()->subMonths(2),
                validationFailureCallback(function () use (&$failed): void {
                    $failed++;
                }),
            );
        }

        expect($failed)->toBe(3);
    });

    test('reads the entered day in the time zone of the promotion', function (
        string $timezone,
        string $startedAt,
        string $enteredDay,
        bool $fails,
    ) {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $promotion = Promotion::factory()->create(['timezone' => $timezone]);
        $manager = Manager::factory()
            ->for($promotion, 'promotion')
            ->has(Employment::factory()->started(Date::parse($startedAt)), 'employments')
            ->create();
        $failed = false;

        // Act
        new CanChangeEmploymentDate($manager)->validate(
            'employment_date',
            $enteredDay,
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        // Assert
        expect($failed)->toBe($fails);
    })->with([
        'west of UTC keeps the day employment started' => ['America/Los_Angeles', '2026-10-11 07:00:00', '2026-10-11', false],
        'west of UTC rejects the local day before' => ['America/Los_Angeles', '2026-10-11 07:00:00', '2026-10-10', true],
        'east of UTC keeps the day employment started' => ['Australia/Sydney', '2026-10-10 13:00:00', '2026-10-11', false],
        'east of UTC rejects the local day before' => ['Australia/Sydney', '2026-10-10 13:00:00', '2026-10-10', true],
    ]);

    test('keeps the day employment started when it started later in that day', function (string $model): void {
        // Arrange
        Date::setTestNow('2026-10-12 18:00:00');
        $employment = Employment::factory()->started(Date::parse('2026-10-12 15:30:00'));
        $employable = match ($model) {
            'tag team' => TagTeam::factory()->has($employment, 'employments')->create(),
            default => Wrestler::factory()->has($employment, 'employments')->create(),
        };
        $failed = false;

        // Act
        new CanChangeEmploymentDate($employable)->validate(
            'employment_date',
            '2026-10-12',
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        // Assert
        expect($failed)->toBeFalse();
    })->with(['tag team', 'wrestler']);

    test('lets a scheduled employment move to today in the promotions time zone but not to an earlier day', function (string $day, bool $fails): void {
        // Arrange
        Date::setTestNow('2026-10-12 03:00:00');
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $wrestler = Wrestler::factory()
            ->for($promotion)
            ->has(Employment::factory()->started(Date::parse('2026-11-01')), 'employments')
            ->create();
        $failed = false;

        // Act
        new CanChangeEmploymentDate($wrestler)->validate(
            'employment_date',
            $day,
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        // Assert
        expect($failed)->toBe($fails);
    })->with([
        'the promotions today' => ['2026-10-11', false],
        'the promotions yesterday' => ['2026-10-10', true],
    ]);

    test('rejects the day before employment started when it started later in the day', function (string $model): void {
        // Arrange
        Date::setTestNow('2026-10-12 18:00:00');
        $employment = Employment::factory()->started(Date::parse('2026-10-12 15:30:00'));
        $employable = match ($model) {
            'tag team' => TagTeam::factory()->has($employment, 'employments')->create(),
            default => Wrestler::factory()->has($employment, 'employments')->create(),
        };
        $failed = false;

        // Act
        new CanChangeEmploymentDate($employable)->validate(
            'employment_date',
            '2026-10-11',
            validationFailureCallback(function () use (&$failed): void {
                $failed = true;
            }),
        );

        // Assert
        expect($failed)->toBeTrue();
    })->with(['tag team', 'wrestler']);
});
