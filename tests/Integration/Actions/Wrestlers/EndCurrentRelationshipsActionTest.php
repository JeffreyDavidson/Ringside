<?php

declare(strict_types=1);

use App\Actions\Wrestlers\DeleteAction;
use App\Actions\Wrestlers\EndCurrentRelationshipsAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Carbon;

use function Pest\Laravel\travelTo;

test('it ends the wrestlers current professional relationships', function () {
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $stable = Stable::factory()->create();
    $manager = Manager::factory()->create();
    $title = Title::factory()->create();
    $effectiveDate = now();

    $wrestler->tagTeams()->attach($tagTeam, ['joined_at' => now()->subDay()]);
    $wrestler->stables()->attach($stable, ['joined_at' => now()->subDay()]);
    $wrestler->managers()->attach($manager, ['hired_at' => now()->subDay()]);
    $championship = TitleChampionship::factory()
        ->for($title, 'title')
        ->for($wrestler, 'champion')
        ->current()
        ->create();

    resolve(EndCurrentRelationshipsAction::class)
        ->handle($wrestler, $effectiveDate);

    $wrestler->refresh();
    $championship->refresh();

    expect($wrestler->currentTagTeam)->toBeNull()
        ->and($wrestler->currentStable)->toBeNull()
        ->and($wrestler->currentManagers)->toBeEmpty()
        ->and($championship->lost_at?->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it locks the tag team before the wrestler and the wrestler before the tag team reigns', function () {
    // Arrange
    $tagTeam = TagTeam::factory()
        ->employed()
        ->create();
    $wrestler = $tagTeam->currentWrestlers()->inLockOrder()->firstOrFail();
    TitleChampionship::factory()
        ->for(Title::factory()->tagTeam(), 'title')
        ->forTagTeam($tagTeam)
        ->current()
        ->create();

    // Act
    $statements = recordStatements(fn () => resolve(ReleaseAction::class)->handle($wrestler, now()));

    // Assert
    $tagTeamLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "tag_teams"'));
    $wrestlerLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "wrestlers"'));
    $reignLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "titles_championships"'));

    expect($tagTeamLock)->toBeLessThan($wrestlerLock)
        ->and($wrestlerLock)->toBeLessThan($reignLock);
});

describe('a member leaving a tag team that holds a title', function (): void {
    test('it vacates the tag team title on the day the wrestler is released, retired or deleted', function (string $actionClass) {
        // Arrange
        travelTo(Carbon::parse('2026-03-10 09:00:00'));
        $tagTeam = TagTeam::factory()
            ->employed()
            ->create();
        $leaver = $tagTeam->currentWrestlers()->inLockOrder()->firstOrFail();
        $partner = $tagTeam->currentWrestlers()->inLockOrder()->get()->last();
        $reign = TitleChampionship::factory()
            ->for(Title::factory()->tagTeam(), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        // Act
        resolve($actionClass)->handle($leaver, now());

        // Assert
        expect($reign->refresh()->lost_at?->toDateTimeString())->toBe('2026-03-10 09:00:00')
            ->and($tagTeam->currentChampionships()->exists())->toBeFalse()
            ->and($tagTeam->currentWrestlers()->whereKey($leaver->id)->exists())->toBeFalse()
            ->and($tagTeam->currentWrestlers()->whereKey($partner->id)->exists())->toBeTrue();
    })->with([
        'release' => ReleaseAction::class,
        'retire' => RetireAction::class,
        'delete' => DeleteAction::class,
    ]);

    test('it vacates only the reigns of the team the wrestler leaves', function () {
        // Arrange
        travelTo(Carbon::parse('2026-03-10 09:00:00'));
        $tagTeam = TagTeam::factory()
            ->employed()
            ->create();
        $otherTagTeam = TagTeam::factory()
            ->employed()
            ->create();
        $reign = TitleChampionship::factory()
            ->for(Title::factory()->tagTeam(), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();
        $otherReign = TitleChampionship::factory()
            ->for(Title::factory()->tagTeam(), 'title')
            ->forTagTeam($otherTagTeam)
            ->current()
            ->create();

        // Act
        resolve(ReleaseAction::class)->handle($tagTeam->currentWrestlers()->inLockOrder()->firstOrFail(), now());

        // Assert
        expect($reign->refresh()->lost_at)->not->toBeNull()
            ->and($otherReign->refresh()->lost_at)->toBeNull();
    });

    test('it keeps the title of a tag team that is itself retiring with the wrestler', function () {
        // Arrange
        $tagTeam = TagTeam::factory()
            ->employed()
            ->create();
        $wrestler = $tagTeam->currentWrestlers()->inLockOrder()->firstOrFail();
        $reign = TitleChampionship::factory()
            ->for(Title::factory()->tagTeam(), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        // Act
        resolve(RetireAction::class)->handle($wrestler, now(), $tagTeam);

        // Assert
        expect($reign->refresh()->lost_at)->toBeNull();
    });

    test('it leaves the title of a tag team that has since lost the wrestler alone', function () {
        // Arrange
        $tagTeam = TagTeam::factory()->create();
        $wrestler = Wrestler::factory()->employed()->create();
        $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => now()->subWeek(), 'left_at' => now()->subDay()]);
        $reign = TitleChampionship::factory()
            ->for(Title::factory()->tagTeam(), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        // Act
        resolve(ReleaseAction::class)->handle($wrestler, now());

        // Assert
        expect($reign->refresh()->lost_at)->toBeNull();
    });
});
