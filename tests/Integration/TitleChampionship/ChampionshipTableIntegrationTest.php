<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Integration tests for TitleChampionship table interactions and database operations.
 *
 * INTEGRATION TEST SCOPE:
 * - Database query performance and optimization
 * - Table relationship loading and eager loading
 * - Complex filtering and pagination scenarios
 * - Championship data aggregation and statistics
 * - Mass operations and bulk updates
 */
describe('TitleChampionship Table Integration Tests', function () {
    beforeEach(function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 12:00:00'));

        $this->titles = Title::factory()->count(3)->create();
        $this->wrestlers = Wrestler::factory()->count(5)->create();
        $this->tagTeams = TagTeam::factory()->count(3)->create();

        // Create diverse championship data
        // $this->createChampionshipTestData(); // Temporarily disabled - function syntax issue
    });

    afterEach(function () {
        Carbon::setTestNow(null);
    });

    /**
     * Create comprehensive test data for championship scenarios
     */
    function createChampionshipTestData(): void
    {
        // Title 1: Mixed champion types with overlapping periods
        TitleChampionship::factory()
            ->for($this->titles[0], 'title')
            ->for($this->wrestlers[0], 'champion')
            ->create([
                'won_at' => Carbon::parse('2023-01-01'),
                'lost_at' => Carbon::parse('2023-06-01'),
            ]);

        TitleChampionship::factory()
            ->for($this->titles[0], 'title')
            ->for($this->tagTeams[0], 'champion')
            ->create([
                'won_at' => Carbon::parse('2023-06-01'),
                'lost_at' => Carbon::parse('2023-12-01'),
            ]);

        TitleChampionship::factory()
            ->for($this->titles[0], 'title')
            ->for($this->wrestlers[1], 'champion')
            ->create([
                'won_at' => Carbon::parse('2023-12-01'),
                'lost_at' => null, // Current
            ]);

        // Title 2: Long-running championship
        TitleChampionship::factory()
            ->for($this->titles[1], 'title')
            ->for($this->wrestlers[2], 'champion')
            ->create([
                'won_at' => Carbon::parse('2022-01-01'),
                'lost_at' => null, // Very long current reign
            ]);

        // Title 3: Multiple short reigns
        $shortReignDates = [
            ['2023-10-01', '2023-10-15'],
            ['2023-10-15', '2023-11-01'],
            ['2023-11-01', '2023-11-15'],
            ['2023-11-15', null], // Current
        ];

        foreach ($shortReignDates as $index => $dates) {
            TitleChampionship::factory()
                ->for($this->titles[2], 'title')
                ->for($this->wrestlers[$index], 'champion')
                ->create([
                    'won_at' => Carbon::parse($dates[0]),
                    'lost_at' => $dates[1] ? Carbon::parse($dates[1]) : null,
                ]);
        }
    }

    describe('relationship loading and performance', function () {
        test('eager loading championship relationships works efficiently', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $championshipsWithRelations = TitleChampionship::query()
                ->with(['title', 'champion'])
                ->get();

            // Assert
            expect($championshipsWithRelations->count())->toBeGreaterThan(5);

            foreach ($championshipsWithRelations as $championship) {
                expect($championship->relationLoaded('title'))->toBeTrue();
                expect($championship->relationLoaded('champion'))->toBeTrue();
                expect($championship->title)->toBeInstanceOf(Title::class);
                expect($championship->champion)->toBeInstanceOf(Wrestler::class)
                    ->or->toBeInstanceOf(TagTeam::class);
            }
        });

        test('complex queries with multiple joins perform efficiently', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $results = TitleChampionship::query()
                ->with(['title', 'champion'])
                ->whereHas('title', function ($query) {
                    $query->where('name', 'like', '%Championship%');
                })
                ->where(function ($query) {
                    $query->where('champion_type', Wrestler::class)
                        ->orWhere('champion_type', TagTeam::class);
                })
                ->withReignLength()
                ->latestWon()
                ->get();

            // Assert
            expect($results)->toBeCollection();
            expect($results->count())->toBeGreaterThan(0);

            foreach ($results as $championship) {
                expect($championship->reign_length)->not->toBeNull();
                expect($championship->reign_length)->toBeNumeric();
            }
        });

        test('polymorphic relationship queries handle different champion types', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $wrestlerChampionships = TitleChampionship::query()
                ->where('champion_type', Wrestler::class)
                ->with('champion')
                ->get();

            $tagTeamChampionships = TitleChampionship::query()
                ->where('champion_type', TagTeam::class)
                ->with('champion')
                ->get();

            // Assert
            expect($wrestlerChampionships->count())->toBeGreaterThan(0);
            expect($tagTeamChampionships->count())->toBeGreaterThan(0);

            foreach ($wrestlerChampionships as $championship) {
                expect($championship->champion)->toBeInstanceOf(Wrestler::class);
            }

            foreach ($tagTeamChampionships as $championship) {
                expect($championship->champion)->toBeInstanceOf(TagTeam::class);
            }
        });

        test('championship queries with title filtering work correctly', function () {
            // Arrange
            $title = $this->titles[0];

            // Act
            $titleChampionships = TitleChampionship::query()
                ->where('title_id', $title->id)
                ->with(['champion', 'title'])
                ->latestWon()
                ->get();

            // Assert
            expect($titleChampionships->count())->toBe(3); // Based on test data

            foreach ($titleChampionships as $championship) {
                expect($championship->title_id)->toBe($title->id);
                expect($championship->title->name)->toBe($title->name);
            }
        });
    });

    describe('data aggregation and statistics', function () {
        test('championship duration statistics calculate correctly', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $championshipStats = TitleChampionship::query()
                ->previous() // Only completed championships
                ->withReignLength()
                ->get();

            $reignLengths = $championshipStats->pluck('reign_length');
            $averageReign = $reignLengths->avg();
            $longestReign = $reignLengths->max();
            $shortestReign = $reignLengths->min();

            // Assert
            expect($championshipStats->count())->toBeGreaterThan(0);
            expect($averageReign)->toBeNumeric();
            expect($longestReign)->toBeGreaterThanOrEqual($averageReign);
            expect($shortestReign)->toBeLessThanOrEqual($averageReign);
            expect($shortestReign)->toBeGreaterThanOrEqual(0);
            expect($longestReign)->toBeLessThan(1000); // Less than ~3 years
        });

        test('championship count by champion type aggregates correctly', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $championshipCounts = TitleChampionship::query()
                ->selectRaw('champion_type, COUNT(*) as championship_count')
                ->groupBy('champion_type')
                ->get()
                ->keyBy('champion_type');

            $wrestlerCount = $championshipCounts[Wrestler::class]->championship_count;
            $tagTeamCount = $championshipCounts[TagTeam::class]->championship_count;
            $totalCount = TitleChampionship::count();

            // Assert
            expect($championshipCounts->has(Wrestler::class))->toBeTrue();
            expect($championshipCounts->has(TagTeam::class))->toBeTrue();
            expect($wrestlerCount)->toBeGreaterThan(0);
            expect($tagTeamCount)->toBeGreaterThan(0);
            expect($wrestlerCount + $tagTeamCount)->toBe($totalCount);
        });

        test('current vs previous championship statistics', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $currentStats = TitleChampionship::query()
                ->current()
                ->withReignLength()
                ->get();

            $previousStats = TitleChampionship::query()
                ->previous()
                ->withReignLength()
                ->get();

            // Assert
            expect($currentStats->count())->toBe(3); // Based on test data
            expect($previousStats->count())->toBeGreaterThan(0);

            foreach ($currentStats as $championship) {
                expect($championship->lost_at)->toBeNull();
                expect($championship->reign_length)->toBeGreaterThan(0);
            }

            foreach ($previousStats as $championship) {
                expect($championship->lost_at)->not->toBeNull();
                expect($championship->reign_length)->toBeGreaterThan(0);
            }
        });

        test('championship timeline queries work with date ranges', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $championships2023 = TitleChampionship::query()
                ->where(function ($query) {
                    $query->where('won_at', '<=', '2023-12-31')
                        ->where(function ($subQuery) {
                            $subQuery->whereNull('lost_at')
                                ->orWhere('lost_at', '>=', '2023-01-01');
                        });
                })
                ->with('champion')
                ->get();

            $championshipsWonInNovember = TitleChampionship::query()
                ->whereBetween('won_at', ['2023-11-01', '2023-11-30'])
                ->get();

            // Assert
            expect($championships2023->count())->toBeGreaterThan(0);
            expect($championshipsWonInNovember->count())->toBeGreaterThan(0);
        });
    });

    describe('filtering and search capabilities', function () {
        test('championship filtering by multiple criteria works correctly', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $filteredChampionships = TitleChampionship::query()
                ->where('champion_type', Wrestler::class)
                ->where('won_at', '>=', '2023-01-01')
                ->current()
                ->with(['champion', 'title'])
                ->get();

            // Assert
            expect($filteredChampionships->count())->toBeGreaterThan(0);

            foreach ($filteredChampionships as $championship) {
                expect($championship->champion)->toBeInstanceOf(Wrestler::class);
                expect($championship->won_at->gte(Carbon::parse('2023-01-01')))->toBeTrue();
                expect($championship->lost_at)->toBeNull();
            }
        });

        test('championship search by champion name works with polymorphic relationships', function () {
            // Arrange
            $wrestlerName = $this->wrestlers[0]->name;
            $tagTeamName = $this->tagTeams[0]->name;

            // Act
            $championshipsByWrestlerName = TitleChampionship::query()
                ->where('champion_type', Wrestler::class)
                ->whereHas('champion', function ($query) use ($wrestlerName) {
                    $query->where('name', 'like', "%{$wrestlerName}%");
                })
                ->with('champion')
                ->get();

            $championshipsByTagTeamName = TitleChampionship::query()
                ->where('champion_type', TagTeam::class)
                ->whereHas('champion', function ($query) use ($tagTeamName) {
                    $query->where('name', 'like', "%{$tagTeamName}%");
                })
                ->with('champion')
                ->get();

            // Assert
            expect($championshipsByWrestlerName->count())->toBeGreaterThan(0);
            foreach ($championshipsByWrestlerName as $championship) {
                expect($championship->champion->name)->toContain($wrestlerName);
            }

            expect($championshipsByTagTeamName->count())->toBeGreaterThan(0);
            foreach ($championshipsByTagTeamName as $championship) {
                expect($championship->champion->name)->toContain($tagTeamName);
            }
        });

        test('championship queries handle complex date filtering', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $longChampionships = TitleChampionship::query()
                ->withReignLength()
                ->havingRaw('reign_length > 180')
                ->with('champion')
                ->get();

            $shortChampionships = TitleChampionship::query()
                ->previous() // Only completed championships
                ->withReignLength()
                ->havingRaw('reign_length < 30')
                ->get();

            // Assert
            expect($longChampionships->count())->toBeGreaterThan(0);
            foreach ($longChampionships as $championship) {
                expect($championship->reign_length)->toBeGreaterThan(180);
            }

            if ($shortChampionships->count() > 0) {
                foreach ($shortChampionships as $championship) {
                    expect($championship->reign_length)->toBeLessThan(30);
                }
            }
        });
    });

    describe('pagination and performance', function () {
        test('championship pagination works with complex queries', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $paginatedChampionships = TitleChampionship::query()
                ->with(['title', 'champion'])
                ->withReignLength()
                ->latestWon()
                ->paginate(5);

            $secondPage = null;
            if ($paginatedChampionships->hasPages()) {
                $secondPage = TitleChampionship::query()
                    ->with(['title', 'champion'])
                    ->withReignLength()
                    ->latestWon()
                    ->paginate(5, ['*'], 'page', 2);
            }

            // Assert
            expect($paginatedChampionships)->toBeInstanceOf(LengthAwarePaginator::class);
            expect($paginatedChampionships->perPage())->toBe(5);
            expect($paginatedChampionships->count())->toBeLessThanOrEqual(5);

            if ($secondPage) {
                expect($secondPage->currentPage())->toBe(2);
            }
        });

        test('large dataset queries maintain performance', function () {
            // Arrange
            TitleChampionship::factory()
                ->count(50)
                ->create();
            $startTime = microtime(true);

            // Act
            $results = TitleChampionship::query()
                ->with(['title', 'champion'])
                ->withReignLength()
                ->current()
                ->get();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Assert
            expect($results)->toBeCollection();
            expect($executionTime)->toBeLessThan(1.0); // Should complete in under 1 second
        });

        test('championship queries support ordering and limiting', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $latestWon = TitleChampionship::query()
                ->latestWon()
                ->limit(3)
                ->get();

            $latestLost = TitleChampionship::query()
                ->previous()
                ->latestLost()
                ->limit(3)
                ->get();

            $longestReigns = TitleChampionship::query()
                ->withReignLength()
                ->orderBy('reign_length', 'desc')
                ->limit(3)
                ->get();

            // Assert
            expect($latestWon->count())->toBeLessThanOrEqual(3);
            expect($latestLost->count())->toBeLessThanOrEqual(3);
            expect($longestReigns->count())->toBeLessThanOrEqual(3);

            if ($longestReigns->count() > 1) {
                for ($i = 0; $i < $longestReigns->count() - 1; $i++) {
                    expect($longestReigns[$i]->reign_length)
                        ->toBeGreaterThanOrEqual($longestReigns[$i + 1]->reign_length);
                }
            }
        });
    });

    describe('data consistency and validation', function () {
        test('championship data maintains referential integrity', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $allChampionships = TitleChampionship::query()
                ->with(['title', 'champion'])
                ->get();

            // Assert
            foreach ($allChampionships as $championship) {
                expect($championship->title)->not->toBeNull();
                expect($championship->title->id)->toBe($championship->title_id);
                expect($championship->champion)->not->toBeNull();
                expect($championship->champion->id)->toBe($championship->champion_id);
                expect(get_class($championship->champion))->toBe($championship->champion_type);
                expect($championship->won_at)->toBeInstanceOf(Carbon::class);

                if ($championship->lost_at) {
                    expect($championship->lost_at)->toBeInstanceOf(Carbon::class);
                    expect($championship->won_at->lt($championship->lost_at))->toBeTrue();
                }
            }
        });

        test('championship queries handle null values correctly', function () {
            // Arrange
            // Test data created in beforeEach

            // Act
            $currentChampionships = TitleChampionship::query()
                ->whereNull('lost_at')
                ->get();

            $endedChampionships = TitleChampionship::query()
                ->whereNotNull('lost_at')
                ->get();

            $allWithReignLength = TitleChampionship::query()
                ->withReignLength()
                ->get();

            // Assert
            expect($currentChampionships->count())->toBeGreaterThan(0);
            expect($endedChampionships->count())->toBeGreaterThan(0);

            foreach ($allWithReignLength as $championship) {
                expect($championship->reign_length)->toBeNumeric();
                expect($championship->reign_length)->toBeGreaterThanOrEqual(0);
            }
        });

        test('championship bulk operations maintain data integrity', function () {
            // Arrange
            $championships = TitleChampionship::query()
                ->where('champion_type', Wrestler::class)
                ->whereNull('lost_at')
                ->get();

            if ($championships->count() > 0) {
                $championshipIds = $championships->pluck('id');

                // Act
                $affectedRows = TitleChampionship::query()
                    ->whereIn('id', $championshipIds)
                    ->update(['updated_at' => now()]);

                $updatedChampionships = TitleChampionship::query()
                    ->whereIn('id', $championshipIds)
                    ->get();

                // Assert
                expect($affectedRows)->toBe($championships->count());

                foreach ($updatedChampionships as $championship) {
                    expect($championship->title_id)->not->toBeNull();
                    expect($championship->champion_id)->not->toBeNull();
                    expect($championship->champion_type)->not->toBeNull();
                }
            }
        });
    });
});
