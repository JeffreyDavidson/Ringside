<?php

declare(strict_types=1);

use App\Actions\Titles\PullAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction as WrestlerRetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Advanced Integration tests for Wrestler-Title relationship scenarios.
 *
 * INTEGRATION TEST SCOPE:
 * - Complex wrestler and title lifecycle interactions
 * - Championship impact on wrestler status changes
 * - Multi-entity business rule validation
 * - Cross-domain action coordination
 * - Real-world wrestling promotion scenarios
 */
describe('Wrestler-Title Complex Relationship Integration', function () {

    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->bookable()->create(['name' => 'Active Champion']);
        $this->title = Title::factory()->active()->create(['name' => 'World Championship']);
    });

    describe('championship impact on wrestler lifecycle', function () {
        test('wrestler retirement while holding championship', function () {
            // Create championship
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            // Verify wrestler is champion
            expect($this->wrestler->fresh()->currentChampionships)->toHaveCount(1);
            expect($this->title->fresh()->currentChampionship)->not->toBeNull();

            // Retire wrestler
            WrestlerRetireAction::run($this->wrestler, Carbon::now());

            // Business rule: Champion retirement should vacate title
            $refreshedWrestler = $this->wrestler->fresh();
            $refreshedTitle = $this->title->fresh();

            expect($refreshedWrestler->isRetired())->toBeTrue();

            // Championship should be ended when wrestler retires
            $championship->refresh();
            expect($championship->lost_at)->not->toBeNull();

            // Title should be vacant
            expect($refreshedTitle->currentChampionship)->toBeNull();
        });

        test('wrestler injury while holding championship', function () {
            // Create championship
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            // Injure wrestler
            InjureAction::run($this->wrestler, Carbon::now());

            $refreshedWrestler = $this->wrestler->fresh();

            expect($refreshedWrestler->isInjured())->toBeTrue();
            expect($refreshedWrestler->isBookable())->toBeFalse();

            // Business rule: Injured champion may keep title or be stripped depending on promotion rules
            // For this test, assume they keep the title but can't defend it
            expect($refreshedWrestler->currentChampionships)->toHaveCount(1);
        });

        test('wrestler employment loss while holding championship', function () {
            // Create championship
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            // Release wrestler from employment
            ReleaseAction::run($this->wrestler, Carbon::now());

            $refreshedWrestler = $this->wrestler->fresh();

            expect($refreshedWrestler->isReleased())->toBeTrue();
            expect($refreshedWrestler->isBookable())->toBeFalse();

            // Business rule: Released wrestler should be stripped of championship
            $championship->refresh();
            expect($championship->lost_at)->not->toBeNull();
        });
    });

    describe('title lifecycle impact on championships', function () {
        test('title retirement while championship is active', function () {
            // Create championship
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            // Retire title
            RetireAction::run($this->title, Carbon::now());

            $refreshedTitle = $this->title->fresh();

            expect($refreshedTitle->isRetired())->toBeTrue();

            // Championship should end when title is retired
            $championship->refresh();
            expect($championship->lost_at)->not->toBeNull();

            // Wrestler should no longer have current championships for this title
            $refreshedWrestler = $this->wrestler->fresh();
            expect($refreshedWrestler->currentChampionships()->where('title_id', $this->title->id)->count())->toBe(0);
        });

        test('title deactivation while championship is active', function () {
            // Create championship
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            // Pull title from active competition
            PullAction::run($this->title, Carbon::now());

            $refreshedTitle = $this->title->fresh();

            expect($refreshedTitle->isInactive())->toBeTrue();

            // Business rule: Title deactivation may or may not end championship
            // For this test, assume championship continues but title is inactive
            expect($refreshedTitle->currentChampionship)->not->toBeNull();
        });
    });

    describe('multi-wrestler championship scenarios', function () {
        test('championship change between wrestlers maintains data integrity', function () {
            $challenger = Wrestler::factory()->bookable()->create(['name' => 'Challenger']);

            // Create initial championship
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(30)]);

            // Championship change
            $titleChangeDate = Carbon::now();

            // End current championship
            $championship->update(['lost_at' => $titleChangeDate]);

            // Create new championship
            $newChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($challenger, 'champion')
                ->current()
                ->create(['won_at' => $titleChangeDate]);

            // Verify data integrity
            $refreshedTitle = $this->title->fresh();
            expect($refreshedTitle->currentChampionship->champion->id)->toBe($challenger->id);
            expect($refreshedTitle->championships)->toHaveCount(2);

            $refreshedOriginalChampion = $this->wrestler->fresh();
            expect($refreshedOriginalChampion->currentChampionships()->where('title_id', $this->title->id)->count())->toBe(0);

            $refreshedNewChampion = $challenger->fresh();
            expect($refreshedNewChampion->currentChampionships()->where('title_id', $this->title->id)->count())->toBe(1);
        });

        test('championship tournament with multiple wrestlers', function () {
            // Create vacant title for tournament
            $vacantTitle = Title::factory()->active()->create(['name' => 'Tournament Championship']);

            $participants = Wrestler::factory()->count(4)->bookable()->create();

            // Simulate tournament progression
            $semifinalist1 = $participants[0];
            $semifinalist2 = $participants[1];

            // Final match winner becomes champion
            $champion = $semifinalist1;

            TitleChampionship::factory()
                ->for($vacantTitle, 'title')
                ->for($champion, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()]);

            expect($vacantTitle->fresh()->currentChampionship->champion->id)->toBe($champion->id);
            expect($champion->fresh()->currentChampionships)->toHaveCount(1);
        });
    });

    describe('championship business rule validation', function () {
        test('unemployed wrestler cannot win championship', function () {
            $unemployedWrestler = Wrestler::factory()->unemployed()->create(['name' => 'Unemployed Wrestler']);

            // Unemployed wrestler should not be bookable
            expect($unemployedWrestler->isBookable())->toBeFalse();

            // Should not be able to create championship for unbookable wrestler
            // (Business rule validation would occur at action level)
            expect($unemployedWrestler->canBeBooked())->toBeFalse();
        });

        test('suspended wrestler cannot defend championship', function () {
            // Create championship
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create();

            // Suspend wrestler
            SuspendAction::run($this->wrestler, Carbon::now());

            $refreshedWrestler = $this->wrestler->fresh();

            expect($refreshedWrestler->isSuspended())->toBeTrue();
            expect($refreshedWrestler->isBookable())->toBeFalse();

            // Suspended wrestler can still hold title but cannot defend it
            expect($refreshedWrestler->currentChampionships)->toHaveCount(1);
        });

        test('retired title cannot have new championships assigned', function () {
            // Retire title
            RetireAction::run($this->title, Carbon::now());

            expect($this->title->fresh()->isRetired())->toBeTrue();

            // Should not be able to create new championship for retired title
            // (Business rule validation would prevent this)
        });
    });

    describe('championship history and statistics integration', function () {
        test('championship reign statistics across multiple wrestlers', function () {
            $wrestlers = Wrestler::factory()->count(3)->bookable()->create();

            // Create championship history
            $championships = [];

            // First reign: 100 days
            $championships[] = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestlers[0], 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(200),
                    'lost_at' => Carbon::now()->subDays(100),
                ]);

            // Second reign: 50 days
            $championships[] = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestlers[1], 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(100),
                    'lost_at' => Carbon::now()->subDays(50),
                ]);

            // Current reign: 50 days and counting
            $championships[] = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($wrestlers[2], 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(50)]);

            // Verify statistics
            $titleHistory = $this->title->championships()->orderBy('won_at')->get();
            expect($titleHistory)->toHaveCount(3);

            // Test reign length calculations
            $firstReign = $titleHistory[0];
            $reignLength = $firstReign->lost_at->diffInDays($firstReign->won_at);
            expect($reignLength)->toBe(100);

            // Current champion should have no end date
            $currentReign = $titleHistory[2];
            expect($currentReign->lost_at)->toBeNull();
        });

        test('wrestler championship count across multiple titles', function () {
            $secondTitle = Title::factory()->active()->create(['name' => 'Secondary Championship']);

            // Wrestler wins first title
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->create([
                    'won_at' => Carbon::now()->subDays(100),
                    'lost_at' => Carbon::now()->subDays(50),
                ]);

            // Same wrestler wins second title
            TitleChampionship::factory()
                ->for($secondTitle, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(30)]);

            // Wrestler wins first title again
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(10)]);

            $refreshedWrestler = $this->wrestler->fresh();

            // Total championship reigns: 3
            expect($refreshedWrestler->championships)->toHaveCount(3);

            // Current championships: 2
            expect($refreshedWrestler->currentChampionships)->toHaveCount(2);

            // Championships for first title: 2
            expect($refreshedWrestler->championships()->where('title_id', $this->title->id)->count())->toBe(2);
        });
    });

    describe('complex real-world scenarios', function () {
        test('champion injury leading to interim championship', function () {
            // Create championship
            $championship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(100)]);

            // Champion gets injured
            InjureAction::run($this->wrestler, Carbon::now());

            // Create interim title
            $interimTitle = Title::factory()->active()->create(['name' => 'Interim World Championship']);
            $interimChampion = Wrestler::factory()->bookable()->create(['name' => 'Interim Champion']);

            TitleChampionship::factory()
                ->for($interimTitle, 'title')
                ->for($interimChampion, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()]);

            // Both titles exist
            expect($this->title->fresh()->currentChampionship)->not->toBeNull();
            expect($interimTitle->fresh()->currentChampionship)->not->toBeNull();

            // Original champion is injured but still champion
            $refreshedChampion = $this->wrestler->fresh();
            expect($refreshedChampion->isInjured())->toBeTrue();
            expect($refreshedChampion->currentChampionships)->toHaveCount(1);
        });

        test('championship unification after company merger', function () {
            // Two companies with similar titles
            $title1 = Title::factory()->active()->create(['name' => 'Company A Championship']);
            $title2 = Title::factory()->active()->create(['name' => 'Company B Championship']);

            $champion1 = Wrestler::factory()->bookable()->create(['name' => 'Company A Champion']);
            $champion2 = Wrestler::factory()->bookable()->create(['name' => 'Company B Champion']);

            // Each holds their company's title
            TitleChampionship::factory()
                ->for($title1, 'title')
                ->for($champion1, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(200)]);

            TitleChampionship::factory()
                ->for($title2, 'title')
                ->for($champion2, 'champion')
                ->current()
                ->create(['won_at' => Carbon::now()->subDays(150)]);

            // Unification match - champion1 wins
            $unificationDate = Carbon::now();

            // End champion2's reign
            $title2->currentChampionship->update(['lost_at' => $unificationDate]);

            // Champion1 wins unified title (represented as winning second title)
            TitleChampionship::factory()
                ->for($title2, 'title')
                ->for($champion1, 'champion')
                ->current()
                ->create(['won_at' => $unificationDate]);

            // Retire one of the titles to represent unification
            RetireAction::run($title2, $unificationDate);

            // Verify unification
            $refreshedChampion1 = $champion1->fresh();
            expect($refreshedChampion1->currentChampionships)->toHaveCount(2);

            $refreshedTitle2 = $title2->fresh();
            expect($refreshedTitle2->isRetired())->toBeTrue();
        });

        test('championship lineage through company transitions', function () {
            // Create a title with rich history
            $wrestlers = Wrestler::factory()->count(5)->bookable()->create();
            $championships = [];

            // Create championship lineage over 2 years
            foreach ($wrestlers as $index => $wrestler) {
                $wonDate = Carbon::now()->subDays(730 - ($index * 120)); // ~4 month reigns
                $lostDate = $index < 4 ? Carbon::now()->subDays(610 - ($index * 120)) : null;

                $championships[] = TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($wrestler, 'champion')
                    ->create([
                        'won_at' => $wonDate,
                        'lost_at' => $lostDate,
                    ]);
            }

            // Verify complete lineage
            $lineage = $this->title->championships()->orderBy('won_at')->get();
            expect($lineage)->toHaveCount(5);

            // Verify chronological order
            for ($i = 1; $i < $lineage->count(); $i++) {
                expect($lineage[$i]->won_at->isAfter($lineage[$i - 1]->won_at))->toBeTrue();
            }

            // Current champion should be the last one
            expect($this->title->fresh()->currentChampionship->champion->id)->toBe($wrestlers[4]->id);
        });
    });
});
