<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('wrestlers may be filtered by employment status', function (): void {
    $employed = Wrestler::factory()->employed()->create();
    $unemployed = Wrestler::factory()->unemployed()->create();
    $released = Wrestler::factory()->released()->create();
    $futureEmployed = Wrestler::factory()->withFutureEmployment()->create();

    expect(Wrestler::query()->employed()->pluck('id')->all())->toBe([$employed->id])
        ->and(Wrestler::query()->unemployed()->pluck('id')->all())->toBe([$unemployed->id])
        ->and(Wrestler::query()->released()->pluck('id')->all())->toBe([$released->id])
        ->and(Wrestler::query()->futureEmployed()->pluck('id')->all())->toBe([$futureEmployed->id]);
});

test('managers may be filtered by employment status', function (): void {
    $employed = Manager::factory()->employed()->create();
    $unemployed = Manager::factory()->unemployed()->create();
    $released = Manager::factory()->released()->create();
    $futureEmployed = Manager::factory()->withFutureEmployment()->create();

    expect(Manager::query()->employed()->pluck('id')->all())->toBe([$employed->id])
        ->and(Manager::query()->unemployed()->pluck('id')->all())->toBe([$unemployed->id])
        ->and(Manager::query()->released()->pluck('id')->all())->toBe([$released->id])
        ->and(Manager::query()->futureEmployed()->pluck('id')->all())->toBe([$futureEmployed->id]);
});

test('referees may be filtered by employment status', function (): void {
    $employed = Referee::factory()->employed()->create();
    $unemployed = Referee::factory()->unemployed()->create();
    $released = Referee::factory()->released()->create();
    $futureEmployed = Referee::factory()->withFutureEmployment()->create();

    expect(Referee::query()->employed()->pluck('id')->all())->toBe([$employed->id])
        ->and(Referee::query()->unemployed()->pluck('id')->all())->toBe([$unemployed->id])
        ->and(Referee::query()->released()->pluck('id')->all())->toBe([$released->id])
        ->and(Referee::query()->futureEmployed()->pluck('id')->all())->toBe([$futureEmployed->id]);
});

test('tag teams may be filtered by employment status', function (): void {
    $employed = TagTeam::factory()->employed()->create();
    $unemployed = TagTeam::factory()->unemployed()->create();
    $released = TagTeam::factory()->released()->create();
    $futureEmployed = TagTeam::factory()->withFutureEmployment()->create();

    expect(TagTeam::query()->employed()->pluck('id')->all())->toBe([$employed->id])
        ->and(TagTeam::query()->unemployed()->pluck('id')->all())->toBe([$unemployed->id])
        ->and(TagTeam::query()->released()->pluck('id')->all())->toBe([$released->id])
        ->and(TagTeam::query()->futureEmployed()->pluck('id')->all())->toBe([$futureEmployed->id]);
});

test('wrestlers may be filtered by retirement status', function (): void {
    $retired = Wrestler::factory()->retired()->create();
    Wrestler::factory()->unemployed()->create();

    expect(Wrestler::query()->retired()->pluck('id')->all())->toBe([$retired->id]);
});

test('managers may be filtered by retirement status', function (): void {
    $retired = Manager::factory()->retired()->create();
    Manager::factory()->unemployed()->create();

    expect(Manager::query()->retired()->pluck('id')->all())->toBe([$retired->id]);
});

test('referees may be filtered by retirement status', function (): void {
    $retired = Referee::factory()->retired()->create();
    Referee::factory()->unemployed()->create();

    expect(Referee::query()->retired()->pluck('id')->all())->toBe([$retired->id]);
});

test('tag teams may be filtered by retirement status', function (): void {
    $retired = TagTeam::factory()->retired()->create();
    TagTeam::factory()->unemployed()->create();

    expect(TagTeam::query()->retired()->pluck('id')->all())->toBe([$retired->id]);
});
