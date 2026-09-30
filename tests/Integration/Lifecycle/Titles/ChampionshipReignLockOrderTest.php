<?php

declare(strict_types=1);

use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('it locks the current reigns of a champion in ascending id order', function (Closure $champion) {
    // Arrange
    $champion = $champion();
    [$firstTitle, $secondTitle, $thirdTitle] = Title::factory()->count(3)->create()->all();
    $reigns = collect([$thirdTitle, $firstTitle, $secondTitle])->map(
        fn (Title $title): TitleChampionship => TitleChampionship::factory()
            ->for($title)
            ->for($champion, 'champion')
            ->current()
            ->create()
    );

    // Act
    $statements = recordStatements(fn () => DB::transaction(
        fn () => resolve(ChampionshipReignManager::class)->endCurrentReignsForChampion($champion, now())
    ));

    // Assert
    $lockingStatement = collect($statements)
        ->firstOrFail(fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "titles_championships"'));
    $updatedReignIds = updatedRowIds($statements, 'titles_championships');

    expect($lockingStatement['sql'])->toContain('order by "titles_championships"."id" asc')
        ->and($updatedReignIds)->toBe($reigns->pluck('id')->sort()->values()->all())
        ->and($reigns->every(fn (TitleChampionship $reign): bool => $reign->refresh()->lost_at !== null))->toBeTrue();
})->with([
    'wrestler' => [fn (): Wrestler => Wrestler::factory()->create()],
    'tag team' => [fn (): TagTeam => TagTeam::factory()->create()],
]);
