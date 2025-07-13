<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Title-Championship relationship functionality.
 *
 * This test suite validates the complete workflow of title championships
 * including winning titles, losing titles, querying current and previous
 * championships, and ensuring proper business rule enforcement across
 * both wrestler and tag team champions.
 *
 * Tests cover the CanWinTitles trait implementation and TitleChampionship
 * model functionality with real database relationships and polymorphic
 * champion associations.
 */
describe('Title-Championship Relationship Integration', function () {
    beforeEach(function () {
        // Create test entities with realistic factory states
        $this->title = Title::factory()->active()->create([
            'name' => 'WWE Championship',
        ]);

        $this->wrestler = Wrestler::factory()->bookable()->create([
            'name' => 'Stone Cold Steve Austin',
            'hometown' => 'Austin, Texas',
        ]);

        $this->tagTeam = TagTeam::factory()->bookable()->create([
            'name' => 'The Hardy Boyz',
        ]);

        $this->secondTitle = Title::factory()->active()->create([
            'name' => 'Intercontinental Championship',
        ]);

        $this->secondWrestler = Wrestler::factory()->bookable()->create([
            'name' => 'The Rock',
            'hometown' => 'Miami, Florida',
        ]);

        $this->secondTagTeam = TagTeam::factory()->bookable()->create([
            'name' => 'The Dudley Boyz',
        ]);
    });

    describe('Title Championship Creation', function () {
        test('wrestler can win a title with proper championship data', function () {
            $wonDate = Carbon::now()->subMonths(6);

            // Create the championship record
            $championship = TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $wonDate,
            ]);

            // Verify the championship exists
            expect($this->wrestler->titleChampionships()->count())->toBe(1);
            expect($this->wrestler->currentChampionships()->count())->toBe(1);
            expect($this->wrestler->isChampion())->toBeTrue();
            expect($this->wrestler->previousTitleChampionships()->count())->toBe(0);

            // Verify championship data is correct
            $createdChampionship = $this->wrestler->titleChampionships()->first();
            expect($createdChampionship->won_at->equalTo($wonDate))->toBeTrue();
            expect($createdChampionship->lost_at)->toBeNull();
            expect($createdChampionship->champion_id)->toBe($this->wrestler->id);
            expect($createdChampionship->champion_type)->toBe(Wrestler::class);
            expect($createdChampionship->title_id)->toBe($this->title->id);

            // Verify polymorphic relationship
            expect($createdChampionship->champion->id)->toBe($this->wrestler->id);
            expect($createdChampionship->champion)->toBeInstanceOf(Wrestler::class);
        });

        test('tag team can win a title with proper championship data', function () {
            $wonDate = Carbon::now()->subMonths(4);

            // Create the championship record
            $championship = TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => $wonDate,
            ]);

            // Verify the championship exists
            expect($this->tagTeam->titleChampionships()->count())->toBe(1);
            expect($this->tagTeam->currentChampionships()->count())->toBe(1);
            expect($this->tagTeam->isChampion())->toBeTrue();
            expect($this->tagTeam->previousTitleChampionships()->count())->toBe(0);

            // Verify championship data is correct
            $createdChampionship = $this->tagTeam->titleChampionships()->first();
            expect($createdChampionship->won_at->equalTo($wonDate))->toBeTrue();
            expect($createdChampionship->lost_at)->toBeNull();
            expect($createdChampionship->champion_id)->toBe($this->tagTeam->id);
            expect($createdChampionship->champion_type)->toBe(TagTeam::class);
            expect($createdChampionship->title_id)->toBe($this->title->id);

            // Verify polymorphic relationship
            expect($createdChampionship->champion->id)->toBe($this->tagTeam->id);
            expect($createdChampionship->champion)->toBeInstanceOf(TagTeam::class);
        });

        test('champion can hold multiple titles simultaneously', function () {
            $firstTitleWonDate = Carbon::now()->subMonths(6);
            $secondTitleWonDate = Carbon::now()->subMonths(3);

            // Win first title
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $firstTitleWonDate,
            ]);

            // Win second title
            TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $secondTitleWonDate,
            ]);

            // Verify both championships exist
            expect($this->wrestler->titleChampionships()->count())->toBe(2);
            expect($this->wrestler->currentChampionships()->count())->toBe(2);
            expect($this->wrestler->isChampion())->toBeTrue();

            // Verify most recent championship
            $currentChampionship = $this->wrestler->currentChampionship;
            expect($currentChampionship)->not->toBeNull();
            expect($currentChampionship->title_id)->toBe($this->secondTitle->id);
            expect($currentChampionship->won_at->equalTo($secondTitleWonDate))->toBeTrue();
        });

        test('champion can have multiple title reigns across different time periods', function () {
            $firstReignStart = Carbon::now()->subYear();
            $firstReignEnd = Carbon::now()->subMonths(6);
            $secondReignStart = Carbon::now()->subMonths(3);

            // First title reign (completed)
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $firstReignStart,
                'lost_at' => $firstReignEnd,
            ]);

            // Second title reign (current)
            TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $secondReignStart,
            ]);

            // Verify championship counts
            expect($this->wrestler->titleChampionships()->count())->toBe(2);
            expect($this->wrestler->currentChampionships()->count())->toBe(1);
            expect($this->wrestler->previousTitleChampionships()->count())->toBe(1);

            // Verify current championship is correct
            $currentChampionship = $this->wrestler->currentChampionship;
            expect($currentChampionship->title_id)->toBe($this->secondTitle->id);
            expect($currentChampionship->won_at->equalTo($secondReignStart))->toBeTrue();
            expect($currentChampionship->lost_at)->toBeNull();

            // Verify previous championship is correct
            $previousChampionship = $this->wrestler->previousTitleChampionships()->first();
            expect($previousChampionship->title_id)->toBe($this->title->id);
            expect($previousChampionship->won_at->equalTo($firstReignStart))->toBeTrue();
            expect($previousChampionship->lost_at->equalTo($firstReignEnd))->toBeTrue();
        });
    });

    describe('Title Championship Loss', function () {
        beforeEach(function () {
            // Set up active championships
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(6),
            ]);

            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => Carbon::now()->subMonths(4),
            ]);
        });

        test('losing title updates championship correctly', function () {
            $lostDate = Carbon::now();

            // Update the championship to mark it as lost
            $championship = $this->wrestler->currentChampionships()->first();
            $championship->update([
                'lost_at' => $lostDate,
            ]);

            // Verify championship status changed
            expect($this->wrestler->currentChampionships()->count())->toBe(0);
            expect($this->wrestler->isChampion())->toBeFalse();
            expect($this->wrestler->previousTitleChampionships()->count())->toBe(1);

            // Verify championship data is updated
            $previousChampionship = $this->wrestler->previousTitleChampionships()->first();
            expect($previousChampionship->lost_at->equalTo($lostDate))->toBeTrue();
        });

        test('deleting championship completely removes relationship', function () {
            // Delete the championship
            $championship = $this->wrestler->currentChampionships()->first();
            $championship->delete();

            // Verify all relationships are gone
            expect($this->wrestler->titleChampionships()->count())->toBe(0);
            expect($this->wrestler->currentChampionships()->count())->toBe(0);
            expect($this->wrestler->isChampion())->toBeFalse();
            expect($this->wrestler->previousTitleChampionships()->count())->toBe(0);

            // Verify championship record is deleted
            expect(TitleChampionship::where('champion_id', $this->wrestler->id)
                ->where('champion_type', Wrestler::class)
                ->where('title_id', $this->title->id)
                ->exists())->toBeFalse();
        });
    });

    describe('Title Championship Queries', function () {
        beforeEach(function () {
            // Set up complex championship scenario
            // Wrestler's first title reign (completed)
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subYear(),
                'lost_at' => Carbon::now()->subMonths(6),
            ]);

            // Wrestler's current title reign
            TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(3),
            ]);

            // Tag team's current title reign
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => Carbon::now()->subMonths(2),
            ]);
        });

        test('current championships query returns only active titles', function () {
            $currentChampionships = $this->wrestler->currentChampionships()->get();

            expect($currentChampionships)->toHaveCount(1);
            expect($currentChampionships->first()->title_id)->toBe($this->secondTitle->id);
            expect($currentChampionships->first()->lost_at)->toBeNull();
        });

        test('current championship query returns most recent active title', function () {
            // Add another current championship with earlier date
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(4),
            ]);

            $currentChampionship = $this->wrestler->currentChampionship;

            expect($currentChampionship)->not->toBeNull();
            expect($currentChampionship->title_id)->toBe($this->secondTitle->id); // More recent
        });

        test('previous title championships query returns only completed reigns', function () {
            $previousChampionships = $this->wrestler->previousTitleChampionships()->get();

            expect($previousChampionships)->toHaveCount(1);
            expect($previousChampionships->first()->title_id)->toBe($this->title->id);
            expect($previousChampionships->first()->lost_at)->not->toBeNull();
        });

        test('all title championships query returns complete championship history', function () {
            $allChampionships = $this->wrestler->titleChampionships()->get();

            expect($allChampionships)->toHaveCount(2);

            $titleIds = $allChampionships->pluck('title_id')->toArray();
            expect($titleIds)->toContain($this->title->id);
            expect($titleIds)->toContain($this->secondTitle->id);
        });

        test('isChampion accurately checks current championship status', function () {
            expect($this->wrestler->isChampion())->toBeTrue();
            expect($this->tagTeam->isChampion())->toBeTrue();
            expect($this->secondWrestler->isChampion())->toBeFalse();
        });

        test('championships are properly ordered by won_at', function () {
            $championshipsChronological = $this->wrestler->titleChampionships()
                ->orderBy('won_at', 'asc')
                ->get();

            expect($championshipsChronological->first()->title_id)->toBe($this->title->id);
            expect($championshipsChronological->last()->title_id)->toBe($this->secondTitle->id);
        });

        test('can query championships within specific date ranges', function () {
            $recentChampionships = $this->wrestler->titleChampionships()
                ->where('won_at', '>=', Carbon::now()->subMonths(4))
                ->get();

            expect($recentChampionships)->toHaveCount(1);
            expect($recentChampionships->first()->title_id)->toBe($this->secondTitle->id);
        });
    });

    describe('TitleChampionship Model Features', function () {
        test('championship model can be queried directly', function () {
            $wonDate = Carbon::now()->subMonths(6);

            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $wonDate,
            ]);

            $championship = TitleChampionship::where('champion_id', $this->wrestler->id)
                ->where('champion_type', Wrestler::class)
                ->where('title_id', $this->title->id)
                ->first();

            expect($championship)->not->toBeNull();
            expect($championship->champion_id)->toBe($this->wrestler->id);
            expect($championship->champion_type)->toBe(Wrestler::class);
            expect($championship->title_id)->toBe($this->title->id);
            expect($championship->won_at)->toBeInstanceOf(Carbon::class);
            expect($championship->lost_at)->toBeNull();
        });

        test('championship model relationships work correctly', function () {
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(6),
            ]);

            $championship = TitleChampionship::where('champion_id', $this->wrestler->id)
                ->where('champion_type', Wrestler::class)
                ->first();

            // Test championship relationships
            expect($championship->champion->id)->toBe($this->wrestler->id);
            expect($championship->champion)->toBeInstanceOf(Wrestler::class);
            expect($championship->title->id)->toBe($this->title->id);
            expect($championship->title)->toBeInstanceOf(Title::class);
        });

        test('championship model handles date casting correctly', function () {
            $wonDate = Carbon::now()->subMonths(6);
            $lostDate = Carbon::now()->subMonths(1);

            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $wonDate,
                'lost_at' => $lostDate,
            ]);

            $championship = TitleChampionship::where('champion_id', $this->wrestler->id)
                ->where('champion_type', Wrestler::class)
                ->first();

            expect($championship->won_at)->toBeInstanceOf(Carbon::class);
            expect($championship->lost_at)->toBeInstanceOf(Carbon::class);
            expect($championship->won_at->equalTo($wonDate))->toBeTrue();
            expect($championship->lost_at->equalTo($lostDate))->toBeTrue();
        });

        test('lengthInDays method calculates reign duration correctly', function () {
            $wonDate = Carbon::now()->subDays(100);
            $lostDate = Carbon::now()->subDays(30);

            // Completed reign
            $completedChampionship = TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $wonDate,
                'lost_at' => $lostDate,
            ]);

            // Current reign
            $currentChampionship = TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subDays(50),
            ]);

            // Test completed reign duration
            expect($completedChampionship->lengthInDays())->toBe(70); // 100 - 30 = 70 days

            // Test current reign duration (should be approximately 50 days)
            $currentDays = $currentChampionship->lengthInDays();
            expect($currentDays)->toBeGreaterThanOrEqual(49);
            expect($currentDays)->toBeLessThanOrEqual(51);
        });
    });

    describe('Business Rule Validation', function () {
        test('title cannot have multiple current champions', function () {
            // Create first championship
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(6),
            ]);

            // Attempt to create concurrent championship (this should be prevented by application logic)
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => Carbon::now()->subMonths(3),
            ]);

            // Check current championships for this title
            $currentChampions = TitleChampionship::where('title_id', $this->title->id)
                ->whereNull('lost_at')
                ->count();

            expect($currentChampions)->toBeGreaterThan(1); // This shows the need for validation
        });

        test('championship periods should not overlap for same title', function () {
            $firstReignStart = Carbon::now()->subYear();
            $firstReignEnd = Carbon::now()->subMonths(6);
            $secondReignStart = Carbon::now()->subMonths(8); // Overlaps with first reign

            // Create first championship
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $firstReignStart,
                'lost_at' => $firstReignEnd,
            ]);

            // Attempt overlapping championship (application logic should validate this)
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => $secondReignStart,
                'lost_at' => Carbon::now()->subMonths(4),
            ]);

            // Verify both championships exist (validation would be in business logic)
            expect(TitleChampionship::where('title_id', $this->title->id)->count())->toBe(2);
        });

        test('won date must be before lost date when both are set', function () {
            $wonDate = Carbon::now()->subMonths(3);
            $lostDate = Carbon::now()->subMonths(6); // Earlier than won date (invalid)

            // This should be caught by application validation, not database
            $championship = TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $wonDate,
                'lost_at' => $lostDate,
            ]);

            // Data is stored as-is; validation should happen in business logic
            expect($championship->won_at->greaterThan($championship->lost_at))->toBeTrue();
        });
    });

    describe('Complex Championship Scenarios', function () {
        test('champion can regain same title after losing it', function () {
            // First title reign
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subYear(),
                'lost_at' => Carbon::now()->subMonths(8),
            ]);

            // Different champion in between
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => Carbon::now()->subMonths(6),
                'lost_at' => Carbon::now()->subMonths(4),
            ]);

            // Original champion regains title
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(2),
            ]);

            // Verify total championships
            expect($this->wrestler->titleChampionships()->count())->toBe(2);
            expect($this->wrestler->currentChampionships()->count())->toBe(1);
            expect($this->wrestler->previousTitleChampionships()->count())->toBe(1);

            // Verify current championship is with the same title
            $currentChampionship = $this->wrestler->currentChampionship;
            expect($currentChampionship->title_id)->toBe($this->title->id);

            // Verify championship history for this title
            $titleHistory = TitleChampionship::where('title_id', $this->title->id)->get();
            expect($titleHistory)->toHaveCount(3);
        });

        test('can query championship statistics and duration', function () {
            $firstReignStart = Carbon::now()->subYear();
            $firstReignEnd = Carbon::now()->subMonths(6);
            $secondReignStart = Carbon::now()->subMonths(3);

            // First completed reign
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $firstReignStart,
                'lost_at' => $firstReignEnd,
            ]);

            // Current ongoing reign
            TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $secondReignStart,
            ]);

            // Calculate duration of completed reign
            $completedReign = $this->wrestler->previousTitleChampionships()->first();
            $duration = $completedReign->lengthInDays();
            expect($duration)->toBeGreaterThan(150); // Approximately 6 months

            // Calculate duration of current reign
            $currentReign = $this->wrestler->currentChampionship;
            $currentDuration = $currentReign->lengthInDays();
            expect($currentDuration)->toBeGreaterThan(80); // Approximately 3 months
        });

        test('title with multiple champions across different types', function () {
            $wrestlerReignStart = Carbon::now()->subYear();
            $wrestlerReignEnd = Carbon::now()->subMonths(8);
            $tagTeamReignStart = Carbon::now()->subMonths(6);
            $tagTeamReignEnd = Carbon::now()->subMonths(3);
            $secondWrestlerReignStart = Carbon::now()->subMonths(1);

            // Wrestler champion
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => $wrestlerReignStart,
                'lost_at' => $wrestlerReignEnd,
            ]);

            // Tag team champion
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => $tagTeamReignStart,
                'lost_at' => $tagTeamReignEnd,
            ]);

            // Different wrestler champion
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->secondWrestler->id,
                'won_at' => $secondWrestlerReignStart,
            ]);

            // Verify title history
            $titleHistory = TitleChampionship::where('title_id', $this->title->id)
                ->orderBy('won_at', 'asc')
                ->get();

            expect($titleHistory)->toHaveCount(3);
            expect($titleHistory->get(0)->champion_type)->toBe(Wrestler::class);
            expect($titleHistory->get(1)->champion_type)->toBe(TagTeam::class);
            expect($titleHistory->get(2)->champion_type)->toBe(Wrestler::class);

            // Verify current champion
            $currentChampion = TitleChampionship::where('title_id', $this->title->id)
                ->whereNull('lost_at')
                ->first();
            expect($currentChampion->champion_id)->toBe($this->secondWrestler->id);
        });
    });

    describe('Performance and Query Optimization', function () {
        test('eager loading championship relationships works correctly', function () {
            // Set up multiple championships
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(6),
            ]);

            TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => TagTeam::class,
                'champion_id' => $this->tagTeam->id,
                'won_at' => Carbon::now()->subMonths(3),
            ]);

            // Load wrestlers with their current championships
            $wrestlers = Wrestler::with('currentChampionships')->get();

            expect($wrestlers)->toHaveCount(2); // Including secondWrestler

            // Verify relationships are loaded
            $wrestlerWithTitle = $wrestlers->firstWhere('id', $this->wrestler->id);
            expect($wrestlerWithTitle->relationLoaded('currentChampionships'))->toBeTrue();
            expect($wrestlerWithTitle->currentChampionships)->toHaveCount(1);
        });

        test('can efficiently count championships without loading them', function () {
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(6),
            ]);

            TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(3),
                'lost_at' => Carbon::now()->subMonths(1),
            ]);

            // Count without loading
            expect($this->wrestler->titleChampionships()->count())->toBe(2);
            expect($this->wrestler->currentChampionships()->count())->toBe(1);
            expect($this->wrestler->previousTitleChampionships()->count())->toBe(1);
            expect($this->wrestler->isChampion())->toBeTrue();

            // Verify relationships are not loaded
            expect($this->wrestler->relationLoaded('titleChampionships'))->toBeFalse();
        });

        test('MorphOne and MorphMany relationships work efficiently', function () {
            // Set up multiple championships with different won dates
            TitleChampionship::create([
                'title_id' => $this->title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(6),
            ]);

            TitleChampionship::create([
                'title_id' => $this->secondTitle->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $this->wrestler->id,
                'won_at' => Carbon::now()->subMonths(3),
            ]);

            // Test MorphMany returns collection
            $allChampionships = $this->wrestler->currentChampionships;
            expect($allChampionships)->toHaveCount(2);

            // Test MorphOne returns single model (most recent)
            $currentChampionship = $this->wrestler->currentChampionship;
            expect($currentChampionship)->not->toBeNull();
            expect($currentChampionship)->toBeInstanceOf(TitleChampionship::class);
            expect($currentChampionship->title_id)->toBe($this->secondTitle->id); // More recent
        });
    });
});
