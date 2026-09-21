<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Models\Lifecycle\Retirement;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

test('employment statuses map to their shared roster query constraints', function (): void {
    $employed = Wrestler::factory()->employed()->create();
    $futureEmployed = Wrestler::factory()->withFutureEmployment()->create();
    $released = Wrestler::factory()->released()->create();
    $retired = Wrestler::factory()->retired()->create();
    $unemployed = Wrestler::factory()->unemployed()->create();

    expect(Wrestler::query()->whereEmploymentStatus(EmploymentStatus::Employed)->pluck('id')->all())->toBe([$employed->id])
        ->and(Wrestler::query()->whereEmploymentStatus(EmploymentStatus::FutureEmployment)->pluck('id')->all())->toBe([$futureEmployed->id])
        ->and(Wrestler::query()->whereEmploymentStatus(EmploymentStatus::Released)->pluck('id')->all())->toBe([$released->id])
        ->and(Wrestler::query()->whereEmploymentStatus(EmploymentStatus::Retired)->pluck('id')->all())->toBe([$retired->id])
        ->and(Wrestler::query()->whereEmploymentStatus(EmploymentStatus::Unemployed)->pluck('id')->all())->toBe([$unemployed->id]);
});

test('wrestlers may be filtered by employment status', function (): void {
    // Arrange
    $employed = Wrestler::factory()->employed()->create();
    $unemployed = Wrestler::factory()->unemployed()->create();
    $released = Wrestler::factory()->released()->create();
    $futureEmployed = Wrestler::factory()->withFutureEmployment()->create();
    Wrestler::factory()->retired()->create();
    Wrestler::factory()->employed()->trashed()->create();
    Wrestler::factory()->unemployed()->trashed()->create();
    Wrestler::factory()->released()->trashed()->create();
    Wrestler::factory()->withFutureEmployment()->trashed()->create();

    // Act
    $employedQuery = Wrestler::query();
    $employedQuery->employed();
    $employedResults = $employedQuery->get();
    $unemployedQuery = Wrestler::query();
    $unemployedQuery->unemployed();
    $unemployedResults = $unemployedQuery->get();
    $releasedQuery = Wrestler::query();
    $releasedQuery->released();
    $releasedResults = $releasedQuery->get();
    $futureEmployedQuery = Wrestler::query();
    $futureEmployedQuery->futureEmployed();
    $futureEmployedResults = $futureEmployedQuery->get();

    // Assert
    expect($employedResults->modelKeys())->toBe([$employed->id])
        ->and($unemployedResults->modelKeys())->toBe([$unemployed->id])
        ->and($releasedResults->modelKeys())->toBe([$released->id])
        ->and($futureEmployedResults->modelKeys())->toBe([$futureEmployed->id]);
});

test('managers may be filtered by employment status', function (): void {
    // Arrange
    $employed = Manager::factory()->employed()->create();
    $unemployed = Manager::factory()->unemployed()->create();
    $released = Manager::factory()->released()->create();
    $futureEmployed = Manager::factory()->withFutureEmployment()->create();
    Manager::factory()->retired()->create();
    Manager::factory()->employed()->trashed()->create();
    Manager::factory()->unemployed()->trashed()->create();
    Manager::factory()->released()->trashed()->create();
    Manager::factory()->withFutureEmployment()->trashed()->create();

    // Act
    $employedQuery = Manager::query();
    $employedQuery->employed();
    $employedResults = $employedQuery->get();
    $unemployedQuery = Manager::query();
    $unemployedQuery->unemployed();
    $unemployedResults = $unemployedQuery->get();
    $releasedQuery = Manager::query();
    $releasedQuery->released();
    $releasedResults = $releasedQuery->get();
    $futureEmployedQuery = Manager::query();
    $futureEmployedQuery->futureEmployed();
    $futureEmployedResults = $futureEmployedQuery->get();

    // Assert
    expect($employedResults->modelKeys())->toBe([$employed->id])
        ->and($unemployedResults->modelKeys())->toBe([$unemployed->id])
        ->and($releasedResults->modelKeys())->toBe([$released->id])
        ->and($futureEmployedResults->modelKeys())->toBe([$futureEmployed->id]);
});

test('referees may be filtered by employment status', function (): void {
    // Arrange
    $employed = Referee::factory()->employed()->create();
    $unemployed = Referee::factory()->unemployed()->create();
    $released = Referee::factory()->released()->create();
    $futureEmployed = Referee::factory()->withFutureEmployment()->create();
    Referee::factory()->retired()->create();
    Referee::factory()->employed()->trashed()->create();
    Referee::factory()->unemployed()->trashed()->create();
    Referee::factory()->released()->trashed()->create();
    Referee::factory()->withFutureEmployment()->trashed()->create();

    // Act
    $employedQuery = Referee::query();
    $employedQuery->employed();
    $employedResults = $employedQuery->get();
    $unemployedQuery = Referee::query();
    $unemployedQuery->unemployed();
    $unemployedResults = $unemployedQuery->get();
    $releasedQuery = Referee::query();
    $releasedQuery->released();
    $releasedResults = $releasedQuery->get();
    $futureEmployedQuery = Referee::query();
    $futureEmployedQuery->futureEmployed();
    $futureEmployedResults = $futureEmployedQuery->get();

    // Assert
    expect($employedResults->modelKeys())->toBe([$employed->id])
        ->and($unemployedResults->modelKeys())->toBe([$unemployed->id])
        ->and($releasedResults->modelKeys())->toBe([$released->id])
        ->and($futureEmployedResults->modelKeys())->toBe([$futureEmployed->id]);
});

test('tag teams may be filtered by employment status', function (): void {
    // Arrange
    $employed = TagTeam::factory()->employed()->create();
    $unemployed = TagTeam::factory()->unemployed()->create();
    $released = TagTeam::factory()->released()->create();
    $futureEmployed = TagTeam::factory()->withFutureEmployment()->create();
    TagTeam::factory()->retired()->create();
    TagTeam::factory()->employed()->trashed()->create();
    TagTeam::factory()->unemployed()->trashed()->create();
    TagTeam::factory()->released()->trashed()->create();
    TagTeam::factory()->withFutureEmployment()->trashed()->create();

    // Act
    $employedQuery = TagTeam::query();
    $employedQuery->employed();
    $employedResults = $employedQuery->get();
    $unemployedQuery = TagTeam::query();
    $unemployedQuery->unemployed();
    $unemployedResults = $unemployedQuery->get();
    $releasedQuery = TagTeam::query();
    $releasedQuery->released();
    $releasedResults = $releasedQuery->get();
    $futureEmployedQuery = TagTeam::query();
    $futureEmployedQuery->futureEmployed();
    $futureEmployedResults = $futureEmployedQuery->get();

    // Assert
    expect($employedResults->modelKeys())->toBe([$employed->id])
        ->and($unemployedResults->modelKeys())->toBe([$unemployed->id])
        ->and($releasedResults->modelKeys())->toBe([$released->id])
        ->and($futureEmployedResults->modelKeys())->toBe([$futureEmployed->id]);
});

test('wrestlers may be filtered by retirement status', function (): void {
    // Arrange
    $retired = Wrestler::factory()->retired()->create();
    Wrestler::factory()->unemployed()->create();
    Wrestler::factory()->retired()->trashed()->create();
    Wrestler::factory()->employed()
        ->has(
            Retirement::factory()
                ->started(Date::now()->subMonth())
                ->ended(Date::now()->subDays(2)),
            'retirements'
        )
        ->create();

    // Act
    $query = Wrestler::query();
    $query->retired();
    $results = $query->get();

    // Assert
    expect($results->modelKeys())->toBe([$retired->id]);
});

test('managers may be filtered by retirement status', function (): void {
    // Arrange
    $retired = Manager::factory()->retired()->create();
    Manager::factory()->unemployed()->create();
    Manager::factory()->retired()->trashed()->create();
    Manager::factory()->employed()
        ->has(
            Retirement::factory()
                ->started(Date::now()->subMonth())
                ->ended(Date::now()->subDays(2)),
            'retirements'
        )
        ->create();

    // Act
    $query = Manager::query();
    $query->retired();
    $results = $query->get();

    // Assert
    expect($results->modelKeys())->toBe([$retired->id]);
});

test('referees may be filtered by retirement status', function (): void {
    // Arrange
    $retired = Referee::factory()->retired()->create();
    Referee::factory()->unemployed()->create();
    Referee::factory()->retired()->trashed()->create();
    Referee::factory()->employed()
        ->has(
            Retirement::factory()
                ->started(Date::now()->subMonth())
                ->ended(Date::now()->subDays(2)),
            'retirements'
        )
        ->create();

    // Act
    $query = Referee::query();
    $query->retired();
    $results = $query->get();

    // Assert
    expect($results->modelKeys())->toBe([$retired->id]);
});

test('tag teams may be filtered by retirement status', function (): void {
    // Arrange
    $retired = TagTeam::factory()->retired()->create();
    TagTeam::factory()->unemployed()->create();
    TagTeam::factory()->retired()->trashed()->create();
    TagTeam::factory()->employed()
        ->has(
            Retirement::factory()
                ->started(Date::now()->subMonth())
                ->ended(Date::now()->subDays(2)),
            'retirements'
        )
        ->create();

    // Act
    $query = TagTeam::query();
    $query->retired();
    $results = $query->get();

    // Assert
    expect($results->modelKeys())->toBe([$retired->id]);
});
