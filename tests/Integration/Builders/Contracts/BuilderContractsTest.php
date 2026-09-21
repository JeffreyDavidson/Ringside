<?php

declare(strict_types=1);

use App\Builders\Roster\ManagerBuilder;
use App\Builders\Roster\RefereeBuilder;
use App\Builders\Roster\TagTeamBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Builders\Titles\TitleBuilder;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

test('models resolve their concrete typed builders', function () {
    expect(Wrestler::query())->toBeInstanceOf(WrestlerBuilder::class)
        ->and(Manager::query())->toBeInstanceOf(ManagerBuilder::class)
        ->and(Referee::query())->toBeInstanceOf(RefereeBuilder::class)
        ->and(TagTeam::query())->toBeInstanceOf(TagTeamBuilder::class)
        ->and(Title::query())->toBeInstanceOf(TitleBuilder::class);
});

test('shared roster query methods retain each concrete builder type', function () {
    expect(Wrestler::query()->employed())->toBeInstanceOf(WrestlerBuilder::class)
        ->and(Manager::query()->employed())->toBeInstanceOf(ManagerBuilder::class)
        ->and(Referee::query()->unemployed())->toBeInstanceOf(RefereeBuilder::class)
        ->and(TagTeam::query()->retired())->toBeInstanceOf(TagTeamBuilder::class);
});

test('domain-specific query methods retain their concrete builder types', function () {
    expect(Wrestler::query()->retired())->toBeInstanceOf(WrestlerBuilder::class)
        ->and(TagTeam::query()->futureEmployed())->toBeInstanceOf(TagTeamBuilder::class)
        ->and(Title::query()->active())->toBeInstanceOf(TitleBuilder::class);
});
