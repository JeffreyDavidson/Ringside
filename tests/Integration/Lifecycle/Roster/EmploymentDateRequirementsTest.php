<?php

declare(strict_types=1);

use App\Exceptions\Lifecycle\EmploymentDateException;
use App\Lifecycle\Roster\EmploymentDateRequirements;
use App\Models\Lifecycle\Employment;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

/**
 * A record of the given type, in the given promotion, with the given employments saved after it.
 *
 * @param  array<int, Employment>  $employments  Unsaved employments, oldest first
 */
function employableWithEmployments(string $type, Promotion $promotion, array $employments): Wrestler|Manager|Referee|TagTeam
{
    $factory = match ($type) {
        'wrestler' => Wrestler::factory(),
        'manager' => Manager::factory(),
        'referee' => Referee::factory(),
        'tag team' => TagTeam::factory(),
        default => throw new LogicException("Unknown employable type {$type}"),
    };

    $record = $factory->unemployed()->for($promotion)->createOne();

    foreach ($employments as $employment) {
        $record->employments()->save($employment);
    }

    return $record;
}

$types = ['wrestler', 'manager', 'referee', 'tag team'];

describe('moving a scheduled employment', function () use ($types): void {
    it('refuses a day before today in the time zone of the promotion', function (string $type): void {
        // Arrange
        Date::setTestNow('2026-10-12 03:00:00');
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $record = employableWithEmployments($type, $promotion, [Employment::factory()->make(['started_at' => Date::parse('2026-11-01')])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureScheduledCanMoveTo($record, Date::parse('2026-10-10 07:00:00'));

        // Assert
        expect($act)->toThrow(EmploymentDateException::class, 'can only move to today or a later day');
    })->with($types);

    it('lets it move to the promotions today or a later day', function (string $day): void {
        // Arrange
        Date::setTestNow('2026-10-12 03:00:00');
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $record = employableWithEmployments('wrestler', $promotion, [Employment::factory()->make(['started_at' => Date::parse('2026-11-01')])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureScheduledCanMoveTo($record, Date::parse($day));

        // Assert
        expect($act)->not->toThrow(EmploymentDateException::class);
    })->with([
        'the promotions today' => ['2026-10-11 07:00:00'],
        'tomorrow' => ['2026-10-12 07:00:00'],
        'next month' => ['2026-11-20 07:00:00'],
    ]);
});

describe('a current employment', function () use ($types): void {
    it('refuses another start day, naming the record', function (string $type): void {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $record = employableWithEmployments($type, Promotion::factory()->create(), [Employment::factory()->make(['started_at' => Date::parse('2026-09-01 09:00:00'), 'ended_at' => null])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureCurrentStartIsUnchanged($record, Date::parse('2026-09-02'));

        // Assert
        expect($act)->toThrow(EmploymentDateException::class, 'is currently employed, so the date employment started cannot be changed');
    })->with($types);

    it('refuses a later day inside the employment, because the start would still move', function (): void {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $record = employableWithEmployments('wrestler', Promotion::factory()->create(), [Employment::factory()->make(['started_at' => Date::parse('2026-09-01'), 'ended_at' => null])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureCurrentStartIsUnchanged($record, Date::parse('2026-10-01'));

        // Assert
        expect($act)->toThrow(EmploymentDateException::class);
    });

    it('keeps the day employment started, whatever time of day it started', function (string $type): void {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $record = employableWithEmployments($type, Promotion::factory()->create(), [Employment::factory()->make(['started_at' => Date::parse('2026-09-01 15:30:00'), 'ended_at' => null])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureCurrentStartIsUnchanged($record, Date::parse('2026-09-01 00:00:00'));

        // Assert
        expect($act)->not->toThrow(EmploymentDateException::class);
    })->with($types);

    it('reads the day in the time zone of the promotion', function (): void {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $record = employableWithEmployments('wrestler', $promotion, [Employment::factory()->make(['started_at' => Date::parse('2026-09-01 02:00:00'), 'ended_at' => null])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureCurrentStartIsUnchanged($record, Date::parse('2026-08-31 07:00:00'));

        // Assert
        expect($act)->not->toThrow(EmploymentDateException::class);
    });

    it('refuses the UTC day when that is another day in the time zone of the promotion', function (): void {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $record = employableWithEmployments('wrestler', $promotion, [Employment::factory()->make(['started_at' => Date::parse('2026-09-01 02:00:00'), 'ended_at' => null])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureCurrentStartIsUnchanged($record, Date::parse('2026-09-01 07:00:00'));

        // Assert
        expect($act)->toThrow(EmploymentDateException::class);
    });

    it('also keeps the day the first employment started, which is the date the tag team form shows', function (): void {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $record = employableWithEmployments('tag team', Promotion::factory()->create(), [
            Employment::factory()->make(['started_at' => Date::parse('2026-01-05'), 'ended_at' => Date::parse('2026-03-01')]),
            Employment::factory()->make(['started_at' => Date::parse('2026-09-01'), 'ended_at' => null]),
        ]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureCurrentStartIsUnchanged($record, Date::parse('2026-01-05'));

        // Assert
        expect($act)->not->toThrow(EmploymentDateException::class);
    });

    it('has nothing to protect when no employment is current', function (): void {
        // Arrange
        Date::setTestNow('2026-10-12 12:00:00');
        $record = employableWithEmployments('wrestler', Promotion::factory()->create(), [Employment::factory()->make(['started_at' => Date::parse('2026-01-05'), 'ended_at' => Date::parse('2026-03-01')])]);

        // Act
        $act = fn () => resolve(EmploymentDateRequirements::class)->ensureCurrentStartIsUnchanged($record, Date::parse('2026-02-01'));

        // Assert
        expect($act)->not->toThrow(EmploymentDateException::class);
    });
});
