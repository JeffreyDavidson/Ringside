<?php

declare(strict_types=1);

use App\Actions\Titles\RetireAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction as WrestlerRetireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @return array{
 *     title: Title,
 *     wrestler: Wrestler,
 *     tagTeam: TagTeam,
 *     secondTitle: Title,
 *     secondWrestler: Wrestler,
 *     secondTagTeam: TagTeam,
 * }
 */
function titlesTitleChampionshipTitleFixtures(): array
{
    Carbon::setTestNow(Carbon::parse('2024-01-15 12:00:00'));

    // Create test entities with realistic factory states
    $title = Title::factory()->active()->create([
        'name' => 'World Championship',
    ]);

    $wrestler = Wrestler::factory()->employed()->create([
        'name' => 'Stone Cold Steve Austin',
        'hometown' => 'Austin, Texas',
    ]);

    $tagTeam = TagTeam::factory()->employed()->create([
        'name' => 'The Hardy Boyz',
    ]);

    $secondTitle = Title::factory()->active()->create([
        'name' => 'Intercontinental Championship',
    ]);

    $secondWrestler = Wrestler::factory()->employed()->create([
        'name' => 'The Rock',
        'hometown' => 'Miami, Florida',
    ]);

    $secondTagTeam = TagTeam::factory()->employed()->create([
        'name' => 'The Dudley Boyz',
    ]);

    // Note: EventMatch creation moved to individual tests when needed
    // since won_match_id is now nullable and not required for basic championship testing

    return [
        'title' => $title,
        'wrestler' => $wrestler,
        'tagTeam' => $tagTeam,
        'secondTitle' => $secondTitle,
        'secondWrestler' => $secondWrestler,
        'secondTagTeam' => $secondTagTeam,
    ];
}

function titlesTitleChampionshipTitleSetupFixtures(Title $title, Wrestler $wrestler, Wrestler $secondWrestler): void
{
    // Set up complex championship scenario
    TitleChampionship::factory()
        ->for($title, 'title')
        ->for($wrestler, 'champion')
        ->create([
            'won_at' => Carbon::now()->subYear(),
            'lost_at' => Carbon::now()->subMonths(6),
        ]);

    TitleChampionship::factory()
        ->for($title, 'title')
        ->for($secondWrestler, 'champion')
        ->create([
            'won_at' => Carbon::now()->subMonths(3),
        ]);

}

/**
 * Integration tests for TitleChampionship model functionality.
 *
 * This test suite validates the complete workflow of title championships
 * including winning titles, losing titles, querying current and previous
 * championships, and ensuring proper business rule enforcement across
 * both wrestler and tag team champions.
 *
 * Consolidated from multiple championship test files to provide comprehensive
 * coverage of championship workflows, table operations, and business rules.
 *
 * @see TitleChampionship
 */
describe('TitleChampionship Model', function () {
    afterEach(function () {
        Carbon::setTestNow(null);
    });

    describe('Championship Creation', function () {
        test('creates championship with factory correctly', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            expect($championship->title_id)->toBe($title->id)
                ->and($championship->champion_id)->toBe($wrestler->id)
                ->and($championship->champion_type)->toBe('wrestler')
                ->and($championship->lost_at)->toBeNull();
        });

        test('supports polymorphic champion relationships', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'tagTeam' => $tagTeam, 'secondTitle' => $secondTitle] = titlesTitleChampionshipTitleFixtures();

            $wrestlerChampionship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            $tagTeamChampionship = TitleChampionship::factory()
                ->for($secondTitle, 'title')
                ->for($tagTeam, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            expect($wrestlerChampionship->champion)->toBeInstanceOf(Wrestler::class)
                ->and($tagTeamChampionship->champion)->toBeInstanceOf(TagTeam::class)
                ->and($wrestlerChampionship->champion_type)->toBe('wrestler')
                ->and($tagTeamChampionship->champion_type)->toBe('tag_team')
                ->and($wrestlerChampionship->champion()->firstOrFail()->getKey())->toBe($wrestler->id)
                ->and($tagTeamChampionship->champion()->firstOrFail()->getKey())->toBe($tagTeam->id);
        });
    });

    describe('Championship Workflow', function () {
        test('championship succession workflow', function ($fromChampionType, $toChampionType, $fromName, $toName) {
            ['title' => $title] = titlesTitleChampionshipTitleFixtures();

            // Create initial champion
            $fromChampion = $fromChampionType::factory()->employed()->create(['name' => $fromName]);
            $toChampion = $toChampionType::factory()->employed()->create(['name' => $toName]);

            // Create initial championship
            $initialChampionship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($fromChampion, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subMonths(6),
                ]);

            // End the first championship
            $initialChampionship->update(['lost_at' => Carbon::now()]);

            // Create new championship
            $newChampionship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($toChampion, 'champion')
                ->create([
                    'won_at' => Carbon::now(),
                ]);

            // Verify succession
            expect(freshModel($title)->currentChampionship()->firstOrFail()->champion()->firstOrFail()->getKey())->toBe($toChampion->id);
            expect($fromChampion->refresh()->currentChampionships()->exists())->toBeFalse()
                ->and($toChampion->refresh()->currentChampionships()->exists())->toBeTrue();
        })->with([
            'wrestler to tag team' => [Wrestler::class, TagTeam::class, 'John Champion', 'Tag Team Champions'],
            'tag team to wrestler' => [TagTeam::class, Wrestler::class, 'Team Champions', 'Jane Challenger'],
            'wrestler to wrestler' => [Wrestler::class, Wrestler::class, 'Current Champion', 'New Champion'],
        ]);

        test('championship duration calculations', function ($days, $period) {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            $wonDate = Carbon::now()->subDays($days);
            $lostDate = Carbon::now();

            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => $wonDate,
                    'lost_at' => $lostDate,
                ]);

            $duration = $championship->won_at->diffInDays($championship->lost_at);

            if ($period === 'week') {
                expect($duration)->toBeLessThan(14);
            } elseif ($period === 'months') {
                expect($duration)->toBeGreaterThan(30);
            } elseif ($period === 'year') {
                expect($duration)->toBeGreaterThan(300);
            }
        })->with([
            'short reign' => [7, 'week'],
            'medium reign' => [90, 'months'],
            'long reign' => [365, 'year'],
        ]);
    });

    describe('Championship Queries', function () {
        test('current championship query returns only active championship', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = titlesTitleChampionshipTitleFixtures();
            titlesTitleChampionshipTitleSetupFixtures($title, $wrestler, $secondWrestler);

            $currentChampionship = $title->currentChampionship()->firstOrFail();

            expect($currentChampionship->champion()->firstOrFail()->getKey())->toBe($secondWrestler->id)
                ->and($currentChampionship->lost_at)->toBeNull();
        });

        test('championship history includes all reigns', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = titlesTitleChampionshipTitleFixtures();
            titlesTitleChampionshipTitleSetupFixtures($title, $wrestler, $secondWrestler);

            $allChampionships = $title->championships()->get();

            expect($allChampionships)->toHaveCount(2);

            $championIds = $allChampionships->pluck('champion_id')->toArray();
            expect($championIds)->toContain($wrestler->id)
                ->toContain($secondWrestler->id);
        });

        test('championships are properly ordered by won_at', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = titlesTitleChampionshipTitleFixtures();
            titlesTitleChampionshipTitleSetupFixtures($title, $wrestler, $secondWrestler);

            $championshipsChronological = $title->championships()
                ->orderBy('won_at', 'asc')
                ->get();

            expect($championshipsChronological->firstOrFail()->champion()->firstOrFail()->getKey())->toBe($wrestler->id)
                ->and($championshipsChronological->reverse()->firstOrFail()->champion()->firstOrFail()->getKey())->toBe($secondWrestler->id);
        });
    });

    describe('Table Operations', function () {
        test('handles bulk championship creation efficiently', function () {
            titlesTitleChampionshipTitleFixtures();

            $wrestlers = Wrestler::factory()->count(5)->employed()->create();
            $titles = Title::factory()->count(3)->active()->create();

            $championships = [];
            foreach ($titles as $titleIndex => $title) {
                foreach ($wrestlers as $wrestlerIndex => $wrestler) {
                    $championships[] = TitleChampionship::factory()
                        ->for($title, 'title')
                        ->for($wrestler, 'champion')
                        ->create([
                            'won_at' => Carbon::now()->subDays($titleIndex * 100 + $wrestlerIndex * 10),
                            'lost_at' => Carbon::now()->subDays($titleIndex * 100 + $wrestlerIndex * 10 - 5),
                        ]);
                }
            }

            expect(TitleChampionship::count())->toBe(15); // 3 titles * 5 wrestlers
        });

        test('eager loading relationships works correctly', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            $championshipsWithRelations = TitleChampionship::with(['title', 'champion'])->get();

            expect($championshipsWithRelations)->toHaveCount(1);

            $championship = $championshipsWithRelations->firstOrFail();
            expect($championship->relationLoaded('title'))->toBeTrue()
                ->and($championship->relationLoaded('champion'))->toBeTrue()
                ->and(requiredModel($championship->title)->name)->toBe('World Championship');
        });

        test('complex filtering scenarios work correctly', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'tagTeam' => $tagTeam, 'secondTitle' => $secondTitle] = titlesTitleChampionshipTitleFixtures();

            // Create multiple championships across different time periods
            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subYear(),
                    'lost_at' => Carbon::now()->subMonths(6),
                ]);

            TitleChampionship::factory()
                ->for($secondTitle, 'title')
                ->for($tagTeam, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subMonths(3),
                ]);

            // Filter current championships
            $currentChampionships = TitleChampionship::whereNull('lost_at')->get();
            expect($currentChampionships)->toHaveCount(1)
                ->and($currentChampionships->firstOrFail()->champion_type)->toBe('tag_team');

            // Filter by champion type
            $wrestlerChampionships = TitleChampionship::where('champion_type', 'wrestler')->get();
            expect($wrestlerChampionships)->toHaveCount(1);

            // Filter by date range
            $recentChampionships = TitleChampionship::where('won_at', '>=', Carbon::now()->subMonths(4))->get();
            expect($recentChampionships)->toHaveCount(1);
        });
    });

    describe('Business Rule Validation', function () {
        test('title cannot have multiple simultaneous champions', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = titlesTitleChampionshipTitleFixtures();

            // Create first championship
            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            // The database refuses a second open reign for the same title
            expect(fn () => DB::transaction(fn () => TitleChampionship::factory()
                ->for($title, 'title')
                ->for($secondWrestler, 'champion')
                ->current()
                ->create()))->toThrow(QueryException::class);
        });

        test('the database rejects a lost date before the won date', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            $wonDate = Carbon::now()->subMonths(3);
            $lostDate = Carbon::now()->subMonths(6);

            expect(fn () => DB::transaction(fn () => TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => $wonDate,
                    'lost_at' => $lostDate,
                ])))->toThrow(QueryException::class);
        });
    });

    describe('Complex Championship Scenarios', function () {
        test('championship can change hands multiple times', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = titlesTitleChampionshipTitleFixtures();

            $champions = [$wrestler, $secondWrestler, $wrestler]; // Wrestler regains title
            $baseDate = Carbon::now()->subYear();

            foreach ($champions as $index => $champion) {
                $wonDate = $baseDate->copy()->addMonths($index * 3);
                $lostDate = $index < count($champions) - 1 ? $wonDate->copy()->addMonths(2) : null;

                TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($champion, 'champion')
                    ->create([
                        'won_at' => $wonDate,
                        'lost_at' => $lostDate,
                    ]);
            }

            // Verify total championships
            expect($title->championships()->count())->toBe(3);

            // Verify current champion is the wrestler (who regained the title)
            $currentChampion = $title->currentChampionship()->firstOrFail()->champion;
            expect($currentChampion->id)->toBe($wrestler->id);

            // Verify championship history includes both wrestlers
            $allChampions = $title->championships()->with('champion')->get();
            $uniqueChampions = $allChampions->pluck('champion.id')->unique();
            expect($uniqueChampions)->toHaveCount(2);
        });

        test('championship statistics and analytics', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = titlesTitleChampionshipTitleFixtures();

            // Create championship history
            $championships = [
                ['champion' => $wrestler, 'won_at' => Carbon::now()->subYear(), 'lost_at' => Carbon::now()->subMonths(6)],
                ['champion' => $secondWrestler, 'won_at' => Carbon::now()->subMonths(3), 'lost_at' => null],
            ];

            foreach ($championships as $championshipData) {
                TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($championshipData['champion'], 'champion')
                    ->create([
                        'won_at' => $championshipData['won_at'],
                        'lost_at' => $championshipData['lost_at'],
                    ]);
            }

            // Calculate statistics
            $completedChampionships = $title->championships()->whereNotNull('lost_at')->get();
            $currentChampionships = $title->championships()->whereNull('lost_at')->get();

            expect($completedChampionships)->toHaveCount(1)
                ->and($currentChampionships)->toHaveCount(1);

            // Calculate duration of completed championship
            $completedChampionship = $completedChampionships->firstOrFail();
            $duration = $completedChampionship->won_at->diffInDays($completedChampionship->lost_at);
            expect($duration)->toBeGreaterThan(150); // Approximately 6 months

            // Calculate current championship duration
            $currentChampionship = $currentChampionships->firstOrFail();
            $currentDuration = $currentChampionship->won_at->diffInDays(Carbon::now());
            expect($currentDuration)->toBeGreaterThan(80); // Approximately 3 months
        });
    });

    describe('Performance Optimization', function () {
        test('efficiently counts championships without loading them', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'secondTitle' => $secondTitle, 'secondWrestler' => $secondWrestler] = titlesTitleChampionshipTitleFixtures();

            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            TitleChampionship::factory()
                ->for($secondTitle, 'title')
                ->for($secondWrestler, 'champion')
                ->current()
                ->create();

            // Count without loading
            expect(TitleChampionship::count())->toBe(2);
            expect($title->championships()->count())->toBe(1)
                ->and($wrestler->titleChampionships()->count())->toBe(1);

            // Verify relationships are not loaded
            expect($title->relationLoaded('championships'))->toBeFalse();
            expect($wrestler->relationLoaded('championships'))->toBeFalse();
        });

        test('polymorphic relationships work efficiently', function () {
            ['title' => $title, 'wrestler' => $wrestler, 'tagTeam' => $tagTeam, 'secondTitle' => $secondTitle] = titlesTitleChampionshipTitleFixtures();

            $wrestlerChampionship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            $tagTeamChampionship = TitleChampionship::factory()
                ->for($secondTitle, 'title')
                ->for($tagTeam, 'champion')
                ->current()
                ->create();

            // Load championships with polymorphic relations
            $championships = TitleChampionship::with('champion')->get();

            expect($championships)->toHaveCount(2)
                ->and($championships->map(fn (TitleChampionship $championship): string => $championship->champion::class)->all())
                ->toEqualCanonicalizing([Wrestler::class, TagTeam::class]);
        });
    });

    describe('Championship Business Rules and Lifecycle', function () {
        test('wrestler retirement while holding championship', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            // Create championship
            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            // Verify wrestler is champion
            expect(freshModel($wrestler)->titleChampionships)->toHaveCount(1);
            expect(freshModel($title)->currentChampionship)->not()->toBeNull();

            // Retire wrestler
            resolve(WrestlerRetireAction::class)->handle($wrestler, Carbon::now());

            // Business rule: Champion retirement should vacate title
            $refreshedWrestler = freshModel($wrestler);
            $refreshedTitle = freshModel($title);

            expect($refreshedWrestler->currentRetirement()->exists())->toBeTrue();

            // Championship should be ended when wrestler retires
            $championship->refresh();
            expect($championship->lost_at)->not()->toBeNull();

            // Title should be vacant
            expect($refreshedTitle->currentChampionship)->toBeNull();
        });

        test('wrestler injury while holding championship', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            // Create championship
            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            // Injure wrestler
            resolve(InjureAction::class)->handle($wrestler, Carbon::now());

            $refreshedWrestler = freshModel($wrestler);

            expect($refreshedWrestler->currentInjury()->exists())->toBeTrue()
                ->and(resolve(RosterBookingEligibility::class)->allows($refreshedWrestler))->toBeFalse();

            // Business rule: Injured champion may keep title or be stripped depending on promotion rules
            // For this test, assume they keep the title but can't defend it
            expect($refreshedWrestler->titleChampionships()->whereNull('lost_at')->count())->toBe(1);
        });

        test('wrestler employment loss while holding championship', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            // Create championship
            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            // Release wrestler from employment
            resolve(ReleaseAction::class)->handle($wrestler, Carbon::now());

            $refreshedWrestler = freshModel($wrestler);

            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Released)
                ->and(resolve(RosterBookingEligibility::class)->allows($refreshedWrestler))->toBeFalse();

            // Business rule: Released wrestler should be stripped of championship
            $championship->refresh();
            expect($championship->lost_at)->not()->toBeNull();
        });

        test('title retirement while championship is active', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            // Create championship
            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            // Retire title
            resolve(RetireAction::class)->handle($title, Carbon::now());

            $refreshedTitle = freshModel($title);

            expect($refreshedTitle->currentRetirement()->exists())->toBeTrue();

            // Championship should end when title is retired
            $championship->refresh();
            expect($championship->lost_at)->not()->toBeNull();

            // Wrestler should no longer have current championships for this title
            $refreshedWrestler = freshModel($wrestler);
            expect($refreshedWrestler->titleChampionships()->where('title_id', $title->id)->whereNull('lost_at')->count())->toBe(0);
        });

        test('championship unification scenario', function () {
            ['title' => $title, 'wrestler' => $wrestler] = titlesTitleChampionshipTitleFixtures();

            // Create two titles that will be unified
            $title2 = Title::factory()->active()->create(['name' => 'Secondary Championship']);

            $champion1 = $wrestler;
            $champion2 = Wrestler::factory()->employed()->create(['name' => 'Champion 2']);

            // Each holds one title
            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($champion1, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            TitleChampionship::factory()
                ->for($title2, 'title')
                ->for($champion2, 'champion')
                ->create([
                    'lost_at' => null,
                ]);

            // Champion 1 wins unification match, becomes champion of both titles
            $unificationDate = Carbon::now();

            // End champion2's reign
            $title2->currentChampionship()->firstOrFail()->update(['lost_at' => $unificationDate]);

            // Champion1 wins the second title
            TitleChampionship::factory()
                ->for($title2, 'title')
                ->for($champion1, 'champion')
                ->create([
                    'won_at' => $unificationDate,
                    'lost_at' => null,
                ]);

            // Verify unification
            expect(freshModel($champion1)->titleChampionships()->whereNull('lost_at')->count())->toBe(2);
            expect(freshModel($title2)->currentChampionship()->firstOrFail()->champion()->firstOrFail()->getKey())->toBe($champion1->id);
        });
    });

    describe('Edge Cases and Data Integrity', function () {
        test('handles wrestler with zero championships', function () {
            titlesTitleChampionshipTitleFixtures();

            $wrestler = Wrestler::factory()->employed()->create(['name' => 'Never Champion']);

            // Verify proper handling of wrestler with no championships
            expect($wrestler->titleChampionships)->toBeEmpty();
            expect($wrestler->titleChampionships()->whereNull('lost_at')->count())->toBe(0)
                ->and($wrestler->titleChampionships()->count())->toBe(0);
        });

        test('handles title with no championship history', function () {
            titlesTitleChampionshipTitleFixtures();

            $title = Title::factory()->active()->create(['name' => 'Never Held Title']);

            // Verify proper handling of title with no championships
            expect($title->championships)->toBeEmpty();
            expect($title->currentChampionship)->toBeNull()
                ->and($title->championships()->count())->toBe(0);
        });

        test('handles championship reign ending in the past without replacement', function () {
            titlesTitleChampionshipTitleFixtures();

            $title = Title::factory()->active()->create(['name' => 'Vacant Title']);
            $wrestler = Wrestler::factory()->employed()->create(['name' => 'Former Champion']);

            // Create ended championship with no replacement
            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(100),
                    'lost_at' => Carbon::now()->subDays(30),
                ]);

            // Title should be vacant
            expect(freshModel($title)->currentChampionship)->toBeNull();

            // Verify championship history exists
            expect($title->championships)->toHaveCount(1);
            expect($title->championships->firstOrFail()->lost_at)->not()->toBeNull();
        });

        test('handles championship on exact same timestamp', function () {
            titlesTitleChampionshipTitleFixtures();

            $title = Title::factory()->active()->create(['name' => 'Timestamp Test Title']);
            $wrestler1 = Wrestler::factory()->employed()->create(['name' => 'Wrestler 1']);
            $wrestler2 = Wrestler::factory()->employed()->create(['name' => 'Wrestler 2']);

            $exactTime = Carbon::now();

            // End first championship and start second at exact same time
            $championship1 = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler1, 'champion')
                ->create([
                    'won_at' => $exactTime->copy()->subDays(30),
                    'lost_at' => $exactTime,
                ]);

            $championship2 = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler2, 'champion')
                ->create([
                    'won_at' => $exactTime,
                    'lost_at' => null,
                ]);

            // Verify proper handling of exact timestamps
            expect(freshModel($title)->currentChampionship()->firstOrFail()->champion()->firstOrFail()->getKey())->toBe($wrestler2->id);
            expect(requiredDate(freshModel($championship1)->lost_at)->eq(freshModel($championship2)->won_at))->toBeTrue();
        });

        test('handles extremely long championship reigns', function () {
            titlesTitleChampionshipTitleFixtures();

            $title = Title::factory()->active()->create(['name' => 'Long Reign Title']);
            $wrestler = Wrestler::factory()->employed()->create(['name' => 'Long Reigning Champion']);

            // Create 10-year championship reign
            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subYears(10),
                    'lost_at' => null,
                ]);

            // Calculate reign length
            $reignLength = $championship->won_at->diffInDays(Carbon::now());
            expect($reignLength)->toBeGreaterThan(3650); // More than 10 years

            // Verify current championship is still valid
            expect(freshModel($title)->currentChampionship)->not()->toBeNull();
            expect($title->currentChampionship()->firstOrFail()->champion()->firstOrFail()->getKey())->toBe($wrestler->id);
        });
    });
});

describe('TitleChampionship local reign dates', function () {
    it('shows when a reign began and ended in the title promotion time zone', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $championship = TitleChampionship::factory()
            ->for(Title::factory()->for($promotion, 'promotion'), 'title')
            ->create([
                'won_at' => Carbon::parse('2026-03-02 03:00:00', 'UTC'),
                'lost_at' => Carbon::parse('2026-06-11 02:00:00', 'UTC'),
            ]);

        // Act
        $championship = TitleChampionship::query()->with('title.promotion')->findOrFail($championship->id);

        // Assert
        expect($championship->local_won_at->toDateTimeString())->toBe('2026-03-01 19:00:00')
            ->and($championship->local_won_at->getTimezone()->getName())->toBe('America/Los_Angeles')
            ->and($championship->local_lost_at?->toDateTimeString())->toBe('2026-06-10 19:00:00')
            ->and($championship->won_at->toDateTimeString())->toBe('2026-03-02 03:00:00');
    });

    it('has no local end date while the reign continues', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $championship = TitleChampionship::factory()
            ->for(Title::factory()->for($promotion, 'promotion'), 'title')
            ->current()
            ->create(['won_at' => Carbon::parse('2026-03-02 03:00:00', 'UTC')]);

        // Act
        $championship = TitleChampionship::query()->with('title.promotion')->findOrFail($championship->id);

        // Assert
        expect($championship->local_lost_at)->toBeNull();
    });

    it('uses the application time zone when the title has no promotion', function () {
        // Arrange
        $championship = TitleChampionship::factory()
            ->for(Title::factory()->state(['promotion_id' => null]), 'title')
            ->create(['won_at' => Carbon::parse('2026-03-02 03:00:00', 'UTC')]);

        // Act
        $championship = TitleChampionship::query()->with('title.promotion')->findOrFail($championship->id);

        // Assert
        expect($championship->local_won_at->toDateTimeString())->toBe('2026-03-02 03:00:00');
    });
});
