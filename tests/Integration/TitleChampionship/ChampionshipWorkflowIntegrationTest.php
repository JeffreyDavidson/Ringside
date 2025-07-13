<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration tests for TitleChampionship workflow scenarios.
 *
 * INTEGRATION TEST SCOPE:
 * - Championship transition workflows (won → lost)
 * - Multiple championship management for same title
 * - Polymorphic champion type switching
 * - Date-based championship validation
 * - Championship history and succession patterns
 */

// Dataset for champion transition scenarios
dataset('champion_transitions', [
    'wrestler to tag team' => [Wrestler::class, TagTeam::class, 'John Champion', 'Tag Team Champions'],
    'tag team to wrestler' => [TagTeam::class, Wrestler::class, 'Team Champions', 'Jane Challenger'],
    'wrestler to wrestler' => [Wrestler::class, Wrestler::class, 'Current Champion', 'New Champion'],
]);

// Dataset for championship duration scenarios
dataset('championship_durations', [
    'short reign' => [7, 'week'],
    'medium reign' => [90, 'months'],
    'long reign' => [365, 'year'],
]);

describe('TitleChampionship Workflow Integration Tests', function () {
    beforeEach(function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 12:00:00'));

        $this->title = Title::factory()->active()->create(['name' => 'World Championship']);
        $this->wrestler1 = Wrestler::factory()->create(['name' => 'John Champion']);
        $this->wrestler2 = Wrestler::factory()->create(['name' => 'Jane Challenger']);
        $this->tagTeam = TagTeam::factory()->create(['name' => 'Tag Team Champions']);
    });

    afterEach(function () {
        Carbon::setTestNow(null);
    });

    describe('championship succession workflow', function () {
        test('title can transition from wrestler to tag team champion', function () {
            // Arrange
            $wrestlerChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler1, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2023-12-01'),
                    'lost_at' => null,
                ]);

            // Act - Verify wrestler is current champion
            $currentChampions = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->current()
                ->get();

            // Assert - Initial state
            expect($currentChampions)->toHaveCount(1);
            expect($currentChampions->first()->champion)->toBeInstanceOf(Wrestler::class);

            // Act - End wrestler championship and create tag team championship
            $wrestlerChampionship->update([
                'lost_at' => Carbon::parse('2024-01-01'),
            ]);

            $tagTeamChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->tagTeam, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2024-01-01'),
                    'lost_at' => null,
                ]);

            $currentChampions = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->current()
                ->get();

            $previousChampions = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->previous()
                ->get();

            // Assert - Championship transition completed
            expect($currentChampions)->toHaveCount(1);
            expect($currentChampions->first()->champion)->toBeInstanceOf(TagTeam::class);
            expect($currentChampions->first()->id)->toBe($tagTeamChampionship->id);
            expect($previousChampions)->toHaveCount(1);
            expect($previousChampions->first()->champion)->toBeInstanceOf(Wrestler::class);
        });

        test('multiple championship reigns for same title create proper history', function () {
            // Arrange
            $championships = collect([
                // First reign (ended)
                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($this->wrestler1, 'champion')
                    ->create([
                        'won_at' => Carbon::parse('2023-01-01'),
                        'lost_at' => Carbon::parse('2023-06-01'),
                    ]),

                // Second reign (ended)
                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($this->wrestler2, 'champion')
                    ->create([
                        'won_at' => Carbon::parse('2023-06-01'),
                        'lost_at' => Carbon::parse('2023-12-01'),
                    ]),

                // Current reign
                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($this->tagTeam, 'champion')
                    ->create([
                        'won_at' => Carbon::parse('2023-12-01'),
                        'lost_at' => null,
                    ]),
            ]);

            // Act
            $historyByLatestWon = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->latestWon()
                ->get();

            $current = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->current()
                ->get();

            $previous = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->previous()
                ->latestLost()
                ->get();

            // Assert
            expect($historyByLatestWon)->toHaveCount(3);
            expect($historyByLatestWon->first()->champion)->toBeInstanceOf(TagTeam::class);
            expect($historyByLatestWon->first()->lost_at)->toBeNull();
            expect($historyByLatestWon->get(1)->champion)->toBeInstanceOf(Wrestler::class);
            expect($historyByLatestWon->get(1)->champion->name)->toBe('Jane Challenger');
            expect($historyByLatestWon->get(2)->champion)->toBeInstanceOf(Wrestler::class);
            expect($historyByLatestWon->get(2)->champion->name)->toBe('John Champion');
            expect($current)->toHaveCount(1);
            expect($previous)->toHaveCount(2);
            expect($previous->first()->champion->name)->toBe('Jane Challenger');
        });

        test('championship workflow maintains data integrity throughout transitions', function () {
            // Arrange
            $initialChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler1, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2024-01-01'),
                    'lost_at' => null,
                ]);
            $transitionDate = Carbon::parse('2024-01-10');

            // Assert - Initial state
            expect($initialChampionship->lengthInDays())->toBe(14); // Jan 1 to Jan 15

            // Act - Simulate championship change
            $initialChampionship->update(['lost_at' => $transitionDate]);

            $newChampionship = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler2, 'champion')
                ->create([
                    'won_at' => $transitionDate,
                    'lost_at' => null,
                ]);

            $currentChampions = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->current()
                ->get();

            // Assert - Transition integrity
            $initialChampionship = $initialChampionship->fresh();
            expect($initialChampionship->lengthInDays())->toBe(9); // Jan 1 to Jan 10
            expect($newChampionship->lengthInDays())->toBe(5); // Jan 10 to Jan 15
            expect($initialChampionship->lost_at)->toEqual($newChampionship->won_at);
            expect($currentChampions)->toHaveCount(1);
            expect($currentChampions->first()->id)->toBe($newChampionship->id);
        });
    });

    describe('championship reign calculations', function () {
        test('reign length calculations work across championship transitions', function () {
            // Arrange
            $championships = collect([
                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($this->wrestler1, 'champion')
                    ->create([
                        'won_at' => Carbon::parse('2023-01-01'),
                        'lost_at' => Carbon::parse('2023-01-31'), // 30 days
                    ]),

                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($this->wrestler2, 'champion')
                    ->create([
                        'won_at' => Carbon::parse('2023-01-31'),
                        'lost_at' => Carbon::parse('2023-03-31'), // 59 days (Jan 31 to Mar 31)
                    ]),

                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($this->tagTeam, 'champion')
                    ->create([
                        'won_at' => Carbon::parse('2023-03-31'),
                        'lost_at' => null, // Current: Mar 31, 2023 to Jan 15, 2024 = 290 days
                    ]),
            ]);

            // Act
            $championshipsWithLength = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->withReignLength()
                ->orderBy('won_at')
                ->get();

            // Assert - Individual reign calculations
            expect($championships[0]->lengthInDays())->toBe(30);
            expect($championships[1]->lengthInDays())->toBe(59);
            expect($championships[2]->lengthInDays())->toBe(290);

            // Assert - Query builder reign length calculations
            expect($championshipsWithLength->get(0)->reign_length)->toBe(30);
            expect($championshipsWithLength->get(1)->reign_length)->toBe(59);
            expect($championshipsWithLength->get(2)->reign_length)->toBe(290);
        });

        test('championship history provides accurate statistical data', function () {
            // Arrange
            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler1, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2023-01-01'),
                    'lost_at' => Carbon::parse('2023-07-01'), // 181 days
                ]);

            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->tagTeam, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2023-07-01'),
                    'lost_at' => Carbon::parse('2023-08-01'), // 31 days
                ]);

            TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler2, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2023-08-01'),
                    'lost_at' => null, // Current: 167 days to test date
                ]);

            // Act
            $championshipStats = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->withReignLength()
                ->get();

            $reignLengths = $championshipStats->pluck('reign_length')->sort()->values();
            $currentReigns = $championshipStats->where('lost_at', null);
            $previousReigns = $championshipStats->whereNotNull('lost_at');
            $averagePreviousReignLength = $previousReigns->avg('reign_length');

            // Assert
            expect($reignLengths->get(0))->toBe(31);  // Shortest reign
            expect($reignLengths->get(1))->toBe(167); // Current reign
            expect($reignLengths->get(2))->toBe(181); // Longest completed reign
            expect($currentReigns)->toHaveCount(1);
            expect($previousReigns)->toHaveCount(2);
            expect($averagePreviousReignLength)->toBe(106); // (181 + 31) / 2
        });
    });

    describe('champion type polymorphism workflow', function () {
        test('championship system handles mixed champion types seamlessly', function () {
            // Arrange
            $wrestlerChamp1 = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler1, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2023-01-01'),
                    'lost_at' => Carbon::parse('2023-04-01'),
                ]);

            $tagTeamChamp = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->tagTeam, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2023-04-01'),
                    'lost_at' => Carbon::parse('2023-07-01'),
                ]);

            $wrestlerChamp2 = TitleChampionship::factory()
                ->for($this->title, 'title')
                ->for($this->wrestler2, 'champion')
                ->create([
                    'won_at' => Carbon::parse('2023-07-01'),
                    'lost_at' => null,
                ]);

            // Act
            $allChampionships = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->with('champion')
                ->get();

            $wrestlerChampionships = $allChampionships->filter(function ($championship) {
                return $championship->champion instanceof Wrestler;
            });

            $tagTeamChampionships = $allChampionships->filter(function ($championship) {
                return $championship->champion instanceof TagTeam;
            });

            // Assert
            expect($wrestlerChampionships)->toHaveCount(2);
            expect($tagTeamChampionships)->toHaveCount(1);

            foreach ($allChampionships as $championship) {
                expect($championship->champion)->not->toBeNull();
                expect($championship->champion_type)->toBe(get_class($championship->champion));
                expect($championship->champion_id)->toBe($championship->champion->id);
            }
        });

        test('champion queries work across different polymorphic types', function () {
            // Arrange
            $wrestlerChampionship = TitleChampionship::factory()
                ->for($this->wrestler1, 'champion')
                ->create();

            $tagTeamChampionship = TitleChampionship::factory()
                ->for($this->tagTeam, 'champion')
                ->create();

            // Act
            $wrestlerChampionships = TitleChampionship::query()
                ->where('champion_type', Wrestler::class)
                ->with('champion')
                ->get();

            $tagTeamChampionships = TitleChampionship::query()
                ->where('champion_type', TagTeam::class)
                ->with('champion')
                ->get();

            $wrestler1Championships = TitleChampionship::query()
                ->where('champion_type', Wrestler::class)
                ->where('champion_id', $this->wrestler1->id)
                ->get();

            // Assert
            expect($wrestlerChampionships)->toHaveCount(1);
            expect($tagTeamChampionships)->toHaveCount(1);
            expect($wrestlerChampionships->first()->champion)->toBeInstanceOf(Wrestler::class);
            expect($tagTeamChampionships->first()->champion)->toBeInstanceOf(TagTeam::class);
            expect($wrestler1Championships)->toHaveCount(1);
            expect($wrestler1Championships->first()->champion->id)->toBe($this->wrestler1->id);
        });
    });

    describe('complex championship scenarios', function () {
        test('championship system handles rapid title changes', function () {
            // Arrange
            $baseDate = Carbon::parse('2024-01-01');
            $champions = [$this->wrestler1, $this->wrestler2, $this->tagTeam];

            $championships = collect();
            for ($i = 0; $i < 3; $i++) {
                $wonAt = $baseDate->copy()->addWeeks($i);
                $lostAt = $i < 2 ? $wonAt->copy()->addWeek() : null;

                $championship = TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($champions[$i], 'champion')
                    ->create([
                        'won_at' => $wonAt,
                        'lost_at' => $lostAt,
                    ]);

                $championships->push($championship);
            }

            // Act
            $allChampionships = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->latestWon()
                ->get();

            // Assert
            expect($allChampionships)->toHaveCount(3);
            expect($championships[0]->lengthInDays())->toBe(7);
            expect($championships[1]->lengthInDays())->toBe(7);
            expect($championships[2]->lengthInDays())->toBe(1); // Jan 15 to Jan 15 (current test date)

            // Verify no overlaps in championship periods
            for ($i = 0; $i < $championships->count() - 1; $i++) {
                $current = $championships[$i];
                $next = $championships[$i + 1];

                expect($current->lost_at)->toEqual($next->won_at);
            }
        });

        test('championship system maintains consistency with complex queries', function () {
            // Arrange
            $titleHistory = collect([
                ['champion' => $this->wrestler1, 'won' => '2023-01-01', 'lost' => '2023-06-01'],
                ['champion' => $this->tagTeam, 'won' => '2023-06-01', 'lost' => '2023-09-01'],
                ['champion' => $this->wrestler2, 'won' => '2023-09-01', 'lost' => '2023-12-01'],
                ['champion' => $this->wrestler1, 'won' => '2023-12-01', 'lost' => null], // Current
            ]);

            foreach ($titleHistory as $reign) {
                TitleChampionship::factory()
                    ->for($this->title, 'title')
                    ->for($reign['champion'], 'champion')
                    ->create([
                        'won_at' => Carbon::parse($reign['won']),
                        'lost_at' => $reign['lost'] ? Carbon::parse($reign['lost']) : null,
                    ]);
            }

            // Act
            $previousChampionshipsWithStats = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->previous()
                ->withReignLength()
                ->latestLost()
                ->with('champion')
                ->get();

            $currentChampionshipComplete = TitleChampionship::query()
                ->where('title_id', $this->title->id)
                ->current()
                ->withReignLength()
                ->with(['champion', 'title'])
                ->first();

            // Assert
            expect($previousChampionshipsWithStats)->toHaveCount(3);
            expect($previousChampionshipsWithStats->get(0)->champion->id)->toBe($this->wrestler2->id);
            expect($previousChampionshipsWithStats->get(1)->champion->id)->toBe($this->tagTeam->id);
            expect($previousChampionshipsWithStats->get(2)->champion->id)->toBe($this->wrestler1->id);
            expect($currentChampionshipComplete)->not->toBeNull();
            expect($currentChampionshipComplete->champion->id)->toBe($this->wrestler1->id);
            expect($currentChampionshipComplete->reign_length)->toBe(45); // Dec 1 to Jan 15
            expect($currentChampionshipComplete->title->name)->toBe('World Championship');
        });
    });
});
