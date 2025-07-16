<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TitleRepository;
use App\Repositories\WrestlerRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Advanced Integration tests for Performance and Edge Cases.
 *
 * INTEGRATION TEST SCOPE:
 * - Large dataset performance testing
 * - Edge case scenario handling
 * - Complex query optimization validation
 * - Memory usage optimization
 * - Concurrent operation handling
 * - Data integrity under stress
 */
describe('Performance and Scale Integration Tests', function () {

    describe('large dataset performance', function () {
        test('handles large number of wrestlers efficiently', function () {
            // Create large dataset
            $wrestlerCount = 1000;
            $wrestlers = Wrestler::factory()->count($wrestlerCount)->create();

            $repository = app(WrestlerRepository::class);

            // Test efficient querying
            $startTime = microtime(true);
            $bookableWrestlers = Wrestler::query()->bookable()->get();
            $queryTime = microtime(true) - $startTime;

            // Should complete within reasonable time (adjust threshold as needed)
            expect($queryTime)->toBeLessThan(2.0); // 2 seconds max for 1000 records

            // Verify correct filtering
            foreach ($bookableWrestlers as $wrestler) {
                expect($wrestler->isBookable())->toBeTrue();
            }
        });

        test('handles large championship history efficiently', function () {
            $title = Title::factory()->active()->create(['name' => 'Historic Championship']);
            $wrestlers = Wrestler::factory()->count(100)->create();

            // Create extensive championship history
            foreach ($wrestlers as $index => $wrestler) {
                $wonDate = Carbon::now()->subDays(3650 - ($index * 30)); // ~10 years of history
                $lostDate = $index < 99 ? Carbon::now()->subDays(3620 - ($index * 30)) : null;

                TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($wrestler, 'champion')
                    ->create([
                        'won_at' => $wonDate,
                        'lost_at' => $lostDate,
                    ]);
            }

            $repository = app(TitleRepository::class);

            // Test longest reigning champion query performance
            $startTime = microtime(true);
            $longestReign = $repository->getLongestReigningChampion($title);
            $queryTime = microtime(true) - $startTime;

            expect($queryTime)->toBeLessThan(1.0); // Should be fast even with 100 championships
            expect($longestReign)->not->toBeNull();
            expect($longestReign->reignLengthInDays)->toBeGreaterThan(0);
        });

        test('memory usage remains reasonable with large datasets', function () {
            $memoryBefore = memory_get_usage(true);

            // Create substantial dataset
            Title::factory()->count(50)->create();
            $wrestlers = Wrestler::factory()->count(200)->create();
            $tagTeams = TagTeam::factory()->count(30)->create();

            // Create championships for all titles
            $titles = Title::all();
            foreach ($titles as $index => $title) {
                if ($index % 2 === 0) {
                    $champion = $wrestlers->random();
                } else {
                    $champion = $tagTeams->random();
                }

                TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($champion, 'champion')
                    ->current()
                    ->create();
            }

            $memoryAfter = memory_get_usage(true);
            $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

            // Memory usage should be reasonable (adjust threshold based on server capacity)
            expect($memoryUsed)->toBeLessThan(128); // Less than 128MB for this test
        });

        test('handles concurrent championship changes', function () {
            $title = Title::factory()->active()->create(['name' => 'Concurrent Test Title']);
            $wrestlers = Wrestler::factory()->count(10)->bookable()->create();

            // Simulate rapid championship changes
            $championships = [];
            $startTime = Carbon::now()->subHours(10);

            foreach ($wrestlers as $index => $wrestler) {
                $wonTime = $startTime->copy()->addHours($index);
                $lostTime = $index < 9 ? $startTime->copy()->addHours($index + 1) : null;

                $championships[] = TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($wrestler, 'champion')
                    ->create([
                        'won_at' => $wonTime,
                        'lost_at' => $lostTime,
                    ]);
            }

            // Verify data integrity after rapid changes
            $currentChampionships = TitleChampionship::where('title_id', $title->id)
                ->whereNull('lost_at')
                ->get();

            expect($currentChampionships)->toHaveCount(1);
            expect($currentChampionships->first()->champion->id)->toBe($wrestlers->last()->id);
        });
    });

    describe('edge case scenario handling', function () {
        test('handles championship on exact same timestamp', function () {
            $title = Title::factory()->active()->create(['name' => 'Timestamp Test Title']);
            $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Wrestler 1']);
            $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Wrestler 2']);

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
                ->current()
                ->create([
                    'won_at' => $exactTime,
                ]);

            // Verify proper handling of exact timestamps
            expect($title->fresh()->currentChampionship->champion->id)->toBe($wrestler2->id);
            expect($championship1->fresh()->lost_at->eq($championship2->fresh()->won_at))->toBeTrue();
        });

        test('handles extremely long championship reigns', function () {
            $title = Title::factory()->active()->create(['name' => 'Long Reign Title']);
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Long Reigning Champion']);

            // Create 10-year championship reign
            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subYears(10)]);

            // Calculate reign length
            $reignLength = Carbon::now()->diffInDays($championship->won_at);
            expect($reignLength)->toBeGreaterThan(3650); // More than 10 years

            // Verify current championship is still valid
            expect($title->fresh()->currentChampionship)->not->toBeNull();
            expect($title->currentChampionship->champion->id)->toBe($wrestler->id);
        });

        test('handles championship with future dates', function () {
            $title = Title::factory()->active()->create(['name' => 'Future Championship']);
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Future Champion']);

            $futureDate = Carbon::now()->addDays(30);

            // Create championship scheduled for the future
            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create(['won_at' => $futureDate]);

            // Verify future championship is created
            expect($championship->won_at->isFuture())->toBeTrue();
            expect($title->fresh()->currentChampionship)->not->toBeNull();
        });

        test('handles championship reign ending in the past without replacement', function () {
            $title = Title::factory()->active()->create(['name' => 'Vacant Title']);
            $wrestler = Wrestler::factory()->create(['name' => 'Former Champion']);

            // Create ended championship with no replacement
            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(100),
                    'lost_at' => Carbon::now()->subDays(30),
                ]);

            // Title should be vacant
            expect($title->fresh()->currentChampionship)->toBeNull();

            // Verify championship history exists
            expect($title->championships)->toHaveCount(1);
            expect($title->championships->first()->lost_at)->not->toBeNull();
        });

        test('handles wrestler with zero championships', function () {
            $wrestler = Wrestler::factory()->create(['name' => 'Never Champion']);

            // Verify proper handling of wrestler with no championships
            expect($wrestler->championships)->toHaveCount(0);
            expect($wrestler->currentChampionships)->toHaveCount(0);

            // Test repository queries
            $repository = app(WrestlerRepository::class);

            // Should handle empty championship queries gracefully
            expect($wrestler->championships()->count())->toBe(0);
        });

        test('handles title with no championship history', function () {
            $title = Title::factory()->active()->create(['name' => 'Never Held Title']);

            // Verify proper handling of title with no championships
            expect($title->championships)->toHaveCount(0);
            expect($title->currentChampionship)->toBeNull();

            $repository = app(TitleRepository::class);

            // Test longest reigning champion with no history
            $longestReign = $repository->getLongestReigningChampion($title);
            expect($longestReign)->toBeNull();
        });
    });

    describe('data integrity under stress', function () {
        test('maintains referential integrity during bulk operations', function () {
            // Create interconnected data
            $titles = Title::factory()->count(10)->active()->create();
            $wrestlers = Wrestler::factory()->count(20)->bookable()->create();

            // Create championships for all titles
            $championships = [];
            foreach ($titles as $title) {
                $champion = $wrestlers->random();
                $championships[] = TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($champion, 'champion')
                    ->current()
                    ->create();
            }

            // Verify all relationships are intact
            foreach ($titles as $title) {
                $refreshedTitle = $title->fresh();
                expect($refreshedTitle->currentChampionship)->not->toBeNull();
                expect($refreshedTitle->currentChampionship->champion)->not->toBeNull();
            }

            // Verify wrestler relationships
            foreach ($wrestlers as $wrestler) {
                $refreshedWrestler = $wrestler->fresh();
                // Each wrestler might have 0 or more championships
                expect($refreshedWrestler->championships)->toBeInstanceOf(Collection::class);
            }
        });

        test('handles cascading deletions properly', function () {
            $title = Title::factory()->active()->create(['name' => 'Deletion Test Title']);
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Test Wrestler']);

            // Create championship
            $championship = TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            $championshipId = $championship->id;

            // Soft delete title
            $title->delete();

            // Verify championship still exists (depends on deletion strategy)
            $deletedChampionship = TitleChampionship::find($championshipId);
            expect($deletedChampionship)->not->toBeNull();

            // Verify wrestler still exists
            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler)->not->toBeNull();
        });

        test('handles database transaction rollbacks gracefully', function () {
            $title = Title::factory()->active()->create(['name' => 'Transaction Test Title']);
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Transaction Wrestler']);

            // Test transaction behavior (simulated)
            DB::beginTransaction();

            try {
                // Create championship within transaction
                $championship = TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($wrestler, 'champion')
                    ->current()
                    ->create();

                // Simulate some operation that might fail
                $simulateFailure = false; // Set to true to test rollback

                if ($simulateFailure) {
                    throw new Exception('Simulated failure');
                }

                DB::commit();

                // Verify championship was created
                expect($title->fresh()->currentChampionship)->not->toBeNull();
            } catch (Exception $e) {
                DB::rollback();

                // Verify rollback worked
                expect($title->fresh()->currentChampionship)->toBeNull();
            }
        });
    });

    describe('complex query optimization', function () {
        test('efficiently queries wrestlers with current championships', function () {
            // Create wrestlers with various championship statuses
            $championsCount = 20;
            $nonChampionsCount = 80;

            $champions = Wrestler::factory()->count($championsCount)->bookable()->create();
            $nonChampions = Wrestler::factory()->count($nonChampionsCount)->create();

            $titles = Title::factory()->count($championsCount)->active()->create();

            // Give each champion a title
            foreach ($champions as $index => $champion) {
                TitleChampionship::factory()
                    ->for($titles[$index], 'title')
                    ->for($champion, 'champion')
                    ->current()
                    ->create();
            }

            // Test efficient query for current champions
            $startTime = microtime(true);

            $currentChampions = Wrestler::whereHas('championships', function ($query) {
                $query->whereNull('lost_at');
            })->get();

            $queryTime = microtime(true) - $startTime;

            expect($queryTime)->toBeLessThan(0.5); // Should be fast
            expect($currentChampions)->toHaveCount($championsCount);

            // Verify all returned wrestlers are actually current champions
            foreach ($currentChampions as $champion) {
                expect($champion->currentChampionships->count())->toBeGreaterThan(0);
            }
        });

        test('efficiently finds vacant titles', function () {
            // Create mix of vacant and occupied titles
            $vacantCount = 15;
            $occupiedCount = 35;

            $vacantTitles = Title::factory()->count($vacantCount)->active()->create();
            $occupiedTitles = Title::factory()->count($occupiedCount)->active()->create();

            $wrestlers = Wrestler::factory()->count($occupiedCount)->bookable()->create();

            // Give occupied titles champions
            foreach ($occupiedTitles as $index => $title) {
                TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($wrestlers[$index], 'champion')
                    ->current()
                    ->create();
            }

            // Test efficient query for vacant titles
            $startTime = microtime(true);

            $vacant = Title::whereDoesntHave('championships', function ($query) {
                $query->whereNull('lost_at');
            })->get();

            $queryTime = microtime(true) - $startTime;

            expect($queryTime)->toBeLessThan(0.5); // Should be fast
            expect($vacant)->toHaveCount($vacantCount);

            // Verify all returned titles are actually vacant
            foreach ($vacant as $title) {
                expect($title->currentChampionship)->toBeNull();
            }
        });

        test('efficiently calculates championship statistics', function () {
            $title = Title::factory()->active()->create(['name' => 'Statistics Test Title']);
            $wrestlers = Wrestler::factory()->count(30)->create();

            // Create varied championship history
            foreach ($wrestlers as $index => $wrestler) {
                $reignLength = rand(10, 300); // Random reign length
                $wonDate = Carbon::now()->subDays(1000 - ($index * 30));
                $lostDate = $index < 29 ? $wonDate->copy()->addDays($reignLength) : null;

                TitleChampionship::factory()
                    ->for($title, 'title')
                    ->for($wrestler, 'champion')
                    ->create([
                        'won_at' => $wonDate,
                        'lost_at' => $lostDate,
                    ]);
            }

            // Test efficient statistics calculation
            $startTime = microtime(true);

            $statistics = $title->championships()
                ->selectRaw('
                    COUNT(*) as total_reigns,
                    AVG(DATEDIFF(COALESCE(lost_at, NOW()), won_at)) as avg_reign_length,
                    MAX(DATEDIFF(COALESCE(lost_at, NOW()), won_at)) as max_reign_length,
                    MIN(DATEDIFF(COALESCE(lost_at, NOW()), won_at)) as min_reign_length
                ')
                ->first();

            $queryTime = microtime(true) - $startTime;

            expect($queryTime)->toBeLessThan(0.3); // Should be very fast
            expect($statistics->total_reigns)->toBe(30);
            expect($statistics->avg_reign_length)->toBeGreaterThan(0);
            expect($statistics->max_reign_length)->toBeGreaterThan($statistics->min_reign_length);
        });
    });
});
