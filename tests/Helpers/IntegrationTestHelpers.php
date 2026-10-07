<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration test helper functions for complex scenarios.
 *
 * These functions set up realistic test scenarios that mirror
 * real-world wrestling promotion operations.
 */

/**
 * Create management relationship with proper pivot data.
 *
 * @param  array<string, mixed>  $options
 */
function createManagementRelationship(Wrestler $wrestler, Manager $manager, array $options = []): void
{
    $defaultOptions = [
        'hired_at' => Carbon::now()->subMonths(6),
        'fired_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $pivotData = array_merge($defaultOptions, $options);
    $wrestler->managers()->attach($manager->id, $pivotData);
}

/**
 * Create tag team membership with proper pivot data.
 *
 * @param  array<string, mixed>  $options
 */
function createTagTeamMembership(Wrestler $wrestler, TagTeam $tagTeam, array $options = []): void
{
    $defaultOptions = [
        'joined_at' => Carbon::now()->subMonths(6),
        'left_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $pivotData = array_merge($defaultOptions, $options);
    $wrestler->tagTeams()->attach($tagTeam->id, $pivotData);
}

/**
 * End management relationship by setting fired_at date.
 */
function endManagementRelationship(Wrestler $wrestler, Manager $manager, ?Carbon $endDate = null): void
{
    $endDate ??= Carbon::now();
    $wrestler->managers()->updateExistingPivot($manager->id, [
        'fired_at' => $endDate,
        'updated_at' => now(),
    ]);
}

/**
 * End tag team membership by setting left_at date.
 */
function endTagTeamMembership(Wrestler $wrestler, TagTeam $tagTeam, ?Carbon $endDate = null): void
{
    $endDate ??= Carbon::now();
    $wrestler->tagTeams()->updateExistingPivot($tagTeam->id, [
        'left_at' => $endDate,
        'updated_at' => now(),
    ]);
}

/**
 * Create multiple management periods for complex scenarios.
 *
 * @param  array<int, array{manager: Manager, hired_at: Carbon, fired_at?: Carbon|null}>  $periods
 */
function createManagementHistory(Wrestler $wrestler, array $periods): void
{
    foreach ($periods as $period) {
        createManagementRelationship($wrestler, $period['manager'], [
            'hired_at' => $period['hired_at'],
            'fired_at' => $period['fired_at'] ?? null,
        ]);
    }
}

/**
 * Create multiple tag team membership periods for complex scenarios.
 *
 * @param  array<int, array{tag_team: TagTeam, joined_at: Carbon, left_at?: Carbon|null}>  $periods
 */
function createTagTeamHistory(Wrestler $wrestler, array $periods): void
{
    foreach ($periods as $period) {
        createTagTeamMembership($wrestler, $period['tag_team'], [
            'joined_at' => $period['joined_at'],
            'left_at' => $period['left_at'] ?? null,
        ]);
    }
}

/**
 * Create overlapping relationship periods for validation testing.
 *
 * @return array<string, Carbon|bool>
 */
function createOverlappingManagementPeriods(Wrestler $wrestler, Manager $manager1, Manager $manager2): array
{
    $firstPeriodStart = Carbon::now()->subYear();
    $firstPeriodEnd = Carbon::now()->subMonths(6);
    $secondPeriodStart = Carbon::now()->subMonths(8); // Overlaps

    createManagementRelationship($wrestler, $manager1, [
        'hired_at' => $firstPeriodStart,
        'fired_at' => $firstPeriodEnd,
    ]);

    createManagementRelationship($wrestler, $manager2, [
        'hired_at' => $secondPeriodStart,
        'fired_at' => Carbon::now()->subMonths(4),
    ]);

    return [
        'first_period_start' => $firstPeriodStart,
        'first_period_end' => $firstPeriodEnd,
        'second_period_start' => $secondPeriodStart,
        'overlap_detected' => $secondPeriodStart->between($firstPeriodStart, $firstPeriodEnd),
    ];
}
