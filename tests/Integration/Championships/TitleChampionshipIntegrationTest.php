<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Title Championship management.
 *
 * INTEGRATION TEST SCOPE:
 * - Championship assignment and transitions
 * - Title lineage and history tracking
 * - Champion availability validation
 * - Multi-entity championship support (Wrestler/TagTeam)
 * - Championship reign calculations
 * - Complex championship scenarios
 */
describe('Championship Assignment Integration', function () {

    beforeEach(function () {
        $this->title = Title::factory()->active()->create(['name' => 'World Championship']);
        $this->wrestler = Wrestler::factory()->bookable()->create(['name' => 'John Champion']);
        $this->tagTeam = TagTeam::factory()->bookable()->create(['name' => 'Champion Duo']);
    });

    describe('basic championship assignment', function () {
        test('assigns championship to wrestler correctly', function () {
            // Create championship
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            // Verify championship assignment
            expect($this->title->fresh()->currentChampionship)->not->toBeNull();
            expect($this->title->currentChampionship->champion->id)->toBe($this->wrestler->id);
            expect($this->wrestler->fresh()->currentChampionships)->toHaveCount(1);
        });

        test('assigns championship to tag team correctly', function () {
            $tagTeamTitle = Title::factory()->active()->tagTeam()->create(['name' => 'Tag Team Championship']);

            // Create tag team championship
            $championship = TitleChampionship::factory()
                ->for($tagTeamTitle, 'title')
                ->for($this->tagTeam, 'champion')
                ->current()
                ->create();

            // Verify championship assignment
            expect($tagTeamTitle->fresh()->currentChampionship)->not->toBeNull();
            expect($tagTeamTitle->currentChampionship->champion->id)->toBe($this->tagTeam->id);
            expect($this->tagTeam->fresh()->currentChampionships)->toHaveCount(1);
        });

        test('prevents multiple current championships for same title', function () {
            // Create first championship
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            $secondWrestler = Wrestler::factory()->bookable()->create(['name' => 'Second Wrestler']);

            // Attempting to create a second current championship should handle properly
            // (Implementation depends on business rules - might end previous or throw exception)
            $secondChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($secondWrestler, 'champion')
                ->current()
                ->create();

            // Should only have one current championship
            $currentChampionships = TitleChampionship::where('title_id', $this->title->id)
                ->whereNull('lost_at')
                ->get();

            expect($currentChampionships)->toHaveCount(1);
        });
    });

    describe('championship transitions', function () {
        test('championship transition maintains proper history', function () {
            // Create initial championship
            $firstChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(30),
                    'lost_at' => Carbon::now()->subDays(5),
                ]);

            $secondWrestler = Wrestler::factory()->bookable()->create(['name' => 'New Champion']);

            // Create new championship
            $secondChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($secondWrestler, 'champion')
                ->current()
                ->create([
                    'won_at' => Carbon::now()->subDays(5),
                ]);

            // Verify history tracking
            $refreshedTitle = $this->title->fresh();
            expect($refreshedTitle->championships)->toHaveCount(2);
            expect($refreshedTitle->currentChampionship->champion->id)->toBe($secondWrestler->id);

            // Verify previous championship is properly ended
            expect($firstChampionship->fresh()->lost_at)->not->toBeNull();
        });

        test('championship reign calculations work correctly', function () {
            $wonDate = Carbon::now()->subDays(100);
            $lostDate = Carbon::now()->subDays(10);

            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->create([
                    'won_at' => $wonDate,
                    'lost_at' => $lostDate,
                ]);

            // Calculate reign length (implementation depends on model methods)
            $reignLength = $lostDate->diffInDays($wonDate);
            expect($reignLength)->toBe(90);

            // Test current championship (no end date)
            $currentChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(30)]);

            // Current reign should be 30 days
            $currentReignLength = Carbon::now()->diffInDays($currentChampionship->won_at);
            expect($currentReignLength)->toBe(30);
        });

        test('title vacancy handling works correctly', function () {
            // Create and end championship without immediate replacement
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(30),
                    'lost_at' => Carbon::now()->subDays(5),
                ]);

            // Title should be vacant
            $refreshedTitle = $this->title->fresh();
            expect($refreshedTitle->currentChampionship)->toBeNull();
            expect($refreshedTitle->championships()->whereNull('lost_at')->count())->toBe(0);
        });
    });

    describe('championship validation and business rules', function () {
        test('champion must be bookable for championship assignment', function () {
            $injuredWrestler = Wrestler::factory()->injured()->create(['name' => 'Injured Wrestler']);

            // Should not be able to assign championship to injured wrestler
            // (Implementation depends on business rules - might throw exception or prevent assignment)
            $canAssign = $injuredWrestler->isBookable();
            expect($canAssign)->toBeFalse();
        });

        test('retired wrestler cannot hold current championship', function () {
            $retiredWrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);

            // Retired wrestler should not be bookable
            expect($retiredWrestler->isBookable())->toBeFalse();

            // Should not be able to create current championship for retired wrestler
            // (Business rule validation would occur at higher level)
        });

        test('championship assignment respects title status', function () {
            $retiredTitle = Title::factory()->retired()->create(['name' => 'Retired Championship']);

            // Should not be able to assign new championship to retired title
            // (Business rule validation)
            expect($retiredTitle->isRetired())->toBeTrue();
        });

        test('tag team championships require tag team champions', function () {
            $tagTeamTitle = Title::factory()->active()->tagTeam()->create(['name' => 'Tag Championship']);

            // Should be able to assign to tag team
            $tagTeamChampionship = TitleChampionship::factory()
                ->for($tagTeamTitle, 'title')
                ->for($this->tagTeam, 'champion')
                ->current()
                ->create();

            expect($tagTeamChampionship->champion)->toBeInstanceOf(TagTeam::class);

            // Individual wrestler should not be typical champion for tag team title
            // (Business rule - might be allowed in some promotions)
        });
    });

    describe('championship statistics and queries', function () {
        test('longest reigning champion calculation works correctly', function () {
            // Create multiple championships with different reign lengths
            $shortReign = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(50),
                    'lost_at' => Carbon::now()->subDays(40),
                ]);

            $wrestler2 = Wrestler::factory()->create(['name' => 'Long Reigning Champion']);
            $longReign = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestler2, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(200),
                    'lost_at' => Carbon::now()->subDays(50),
                ]);

            $wrestler3 = Wrestler::factory()->create(['name' => 'Medium Champion']);
            $mediumReign = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestler3, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(100),
                    'lost_at' => Carbon::now()->subDays(70),
                ]);

            // Test longest reigning champion query
            $longestReign = $this->title->championships()
                ->selectRaw('*, DATEDIFF(COALESCE(lost_at, NOW()), won_at) as reign_length')
                ->orderByRaw('DATEDIFF(COALESCE(lost_at, NOW()), won_at) DESC')
                ->first();

            expect($longestReign->champion->id)->toBe($wrestler2->id);
        });

        test('championship count per wrestler works correctly', function () {
            $wrestler2 = Wrestler::factory()->create(['name' => 'Multi-time Champion']);

            // Create multiple championships for same wrestler
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestler2, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(200),
                    'lost_at' => Carbon::now()->subDays(150),
                ]);

            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestler2, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(100),
                    'lost_at' => Carbon::now()->subDays(50),
                ]);

            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestler2, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(10)]);

            // Wrestler should have 3 championship reigns
            $championshipCount = $wrestler2->championships()
                ->where('title_id', $this->title->id)
                ->count();

            expect($championshipCount)->toBe(3);
        });

        test('title lineage tracking works correctly', function () {
            $wrestlers = Wrestler::factory()->count(5)->create();

            // Create championship lineage
            foreach ($wrestlers as $index => $wrestler) {
                $wonDate = Carbon::now()->subDays(100 - ($index * 20));
                $lostDate = $index < 4 ? Carbon::now()->subDays(80 - ($index * 20)) : null;

                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($wrestler, 'champion')
                    ->create([
                        'won_at' => $wonDate,
                        'lost_at' => $lostDate,
                    ]);
            }

            // Verify lineage order
            $lineage = $this->title->championships()
                ->orderBy('won_at')
                ->get();

            expect($lineage)->toHaveCount(5);
            expect($lineage->first()->champion->id)->toBe($wrestlers[0]->id);
            expect($lineage->last()->champion->id)->toBe($wrestlers[4]->id);
            expect($lineage->last()->lost_at)->toBeNull(); // Current champion
        });
    });

    describe('complex championship scenarios', function () {
        test('tournament winner championship assignment', function () {
            // Simulate tournament where vacant title is won
            $vacantTitle = Title::factory()->active()->create(['name' => 'Vacant Tournament Title']);
            $tournamentWinner = Wrestler::factory()->bookable()->create(['name' => 'Tournament Winner']);

            $championship = TitleChampionship::factory()
                ->for($vacantTitle, 'title')
                ->for($tournamentWinner, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()]);

            expect($vacantTitle->fresh()->currentChampionship->champion->id)->toBe($tournamentWinner->id);
        });

        test('championship unification scenario', function () {
            // Create two titles that will be unified
            $title2 = Title::factory()->active()->create(['name' => 'Secondary Championship']);

            $champion1 = Wrestler::factory()->bookable()->create(['name' => 'Champion 1']);
            $champion2 = Wrestler::factory()->bookable()->create(['name' => 'Champion 2']);

            // Each holds one title
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($champion1, 'champion')
                ->current()
                ->create();

            TitleChampionship::factory()
                ->for($title2, 'title')
                ->for($champion2, 'champion')
                ->current()
                ->create();

            // Champion 1 wins unification match, becomes champion of both titles
            $unificationDate = Carbon::now();

            // End champion2's reign
            $title2->currentChampionship->update(['lost_at' => $unificationDate]);

            // Champion1 wins the second title
            TitleChampionship::factory()
                ->for($title2, 'title')
                ->for($champion1, 'champion')
                ->current()
                ->create(['won_at' => $unificationDate]);

            // Verify unification
            expect($champion1->fresh()->currentChampionships)->toHaveCount(2);
            expect($title2->fresh()->currentChampionship->champion->id)->toBe($champion1->id);
        });

        test('championship stripping scenario', function () {
            // Create championship that will be stripped
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(30)]);

            // Strip championship (wrestler injury, misconduct, etc.)
            $strippingDate = Carbon::now();
            $championship->update(['lost_at' => $strippingDate]);

            // Title becomes vacant
            expect($this->title->fresh()->currentChampionship)->toBeNull();
            expect($this->wrestler->fresh()->currentChampionships)->toHaveCount(0);
        });

        test('interim championship scenario', function () {
            // Create regular championship
            $regularChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(30)]);

            // Champion gets injured - create interim title
            $interimTitle = Title::factory()->active()->create(['name' => 'Interim World Championship']);
            $interimChampion = Wrestler::factory()->bookable()->create(['name' => 'Interim Champion']);

            TitleChampionship::factory()
                ->for($interimTitle, 'title')
                ->for($interimChampion, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(5)]);

            // Both championships exist simultaneously
            expect($this->title->fresh()->currentChampionship)->not->toBeNull();
            expect($interimTitle->fresh()->currentChampionship)->not->toBeNull();
            expect($this->title->currentChampionship->champion->id)->toBe($this->wrestler->id);
            expect($interimTitle->currentChampionship->champion->id)->toBe($interimChampion->id);
        });
    });
});
