<?php

declare(strict_types=1);

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\Stables\EstablishAction;
use App\Actions\Stables\RemoveStableMembersAction;
use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Roster\Stables\StableActivityPeriodRequirements;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Lang;

test('each caller names its date range context through a translated label', function (Closure $act, string $context): void {
    // Arrange
    $contexts = [
        'stable_activity',
        'stable_establishment',
        'stable_membership_removal',
        'activity_end',
        'activity_period',
        'employment_period',
    ];

    // Adding lines to a group that is not loaded yet would hide the rest of the group.
    Lang::get('core.all');

    Lang::addLines(
        array_combine(
            array_map(fn (string $name): string => "core.date_range_contexts.{$name}", $contexts),
            array_map(fn (string $name): string => "[{$name}]", $contexts),
        ),
        'en',
    );

    // Act & Assert
    expect($act)->toThrow(InvalidDateRangeException::class, "[{$context}]");
})->with([
    'stable update' => [
        function (): void {
            $stable = Stable::factory()
                ->inactive()
                ->create();
            $startedAt = now()->subMonth();

            resolve(UpdateAction::class)->handle($stable, new StableData(
                name: 'Updated Name',
                start_date: $startedAt,
                members: new StableMembershipData,
                end_date: $startedAt->copy()->subDay(),
            ));
        },
        'stable_activity',
    ],
    'stable activity period move' => [
        function (): void {
            $stable = Stable::factory()
                ->unactivated()
                ->create();
            $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-02-01']);

            resolve(StableActivityPeriodRequirements::class)->ensureCanMove(
                $stable,
                $stable->activityPeriods()->orderBy('id')->get(),
                Date::parse('2026-01-10', 'UTC'),
                Date::parse('2026-01-09', 'UTC'),
            );
        },
        'stable_activity',
    ],
    'stable establishment' => [
        function (): void {
            $activation = now()->subMonth();

            resolve(EstablishAction::class)->handle(
                Stable::factory()->create(),
                $activation,
                $activation->copy()->subDay(),
            );
        },
        'stable_establishment',
    ],
    'stable membership removal' => [
        function (): void {
            resolve(RemoveStableMembersAction::class)->handle(
                Stable::factory()->create(),
                new StableMembershipData,
                now()->addDay(),
            );
        },
        'stable_membership_removal',
    ],
    'future activity end' => [
        function (): void {
            resolve(EndActivityPeriodAction::class)->handle(
                Title::factory()
                    ->active()
                    ->create(),
                now()->addDay(),
            );
        },
        'activity_end',
    ],
    'activity end before its start' => [
        function (): void {
            $title = Title::factory()
                ->active()
                ->create();

            resolve(EndActivityPeriodAction::class)->handle(
                $title,
                $title->currentActivityPeriod()->firstOrFail()->started_at->copy()->subSecond(),
            );
        },
        'activity_period',
    ],
    'employment end before its start' => [
        function (): void {
            $wrestler = Wrestler::factory()->create();
            $wrestler->employments()->create(['started_at' => now()->subDays(10)]);

            resolve(EmploymentPeriodManager::class)->end($wrestler, now()->subDays(20));
        },
        'employment_period',
    ],
]);
