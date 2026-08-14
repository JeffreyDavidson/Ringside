<?php

declare(strict_types=1);

use App\Builders\Roster\ManagerBuilder;
use App\Builders\Roster\RefereeBuilder;
use App\Builders\Roster\TagTeamBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Builders\Titles\TitleBuilder;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;

test('models resolve their concrete typed builders', function () {
    expect(Wrestler::query())->toBeInstanceOf(WrestlerBuilder::class)
        ->and(Manager::query())->toBeInstanceOf(ManagerBuilder::class)
        ->and(Referee::query())->toBeInstanceOf(RefereeBuilder::class)
        ->and(TagTeam::query())->toBeInstanceOf(TagTeamBuilder::class)
        ->and(Title::query())->toBeInstanceOf(TitleBuilder::class);
});

test('shared roster query methods retain each concrete builder type', function () {
    expect(Wrestler::query()->available())->toBeInstanceOf(WrestlerBuilder::class)
        ->and(Manager::query()->employed())->toBeInstanceOf(ManagerBuilder::class)
        ->and(Referee::query()->suspended())->toBeInstanceOf(RefereeBuilder::class)
        ->and(TagTeam::query()->retired())->toBeInstanceOf(TagTeamBuilder::class);
});

test('domain-specific query methods retain their concrete builder types', function () {
    expect(Wrestler::query()->bookable())->toBeInstanceOf(WrestlerBuilder::class)
        ->and(TagTeam::query()->bookable())->toBeInstanceOf(TagTeamBuilder::class)
        ->and(Title::query()->vacant())->toBeInstanceOf(TitleBuilder::class);
});
