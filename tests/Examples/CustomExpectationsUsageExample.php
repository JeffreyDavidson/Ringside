<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;
use App\Models\Managers\Manager;
use App\Models\Titles\Title;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;

/**
 * EXAMPLE: Real-world usage of custom expectations and helpers.
 * 
 * This demonstrates how to use custom expectations and helpers in actual test scenarios
 * to write more readable, maintainable, and expressive tests.
 */

describe('Custom Expectations Usage Examples', function () {
    describe('Wrestling Domain Business Logic Testing', function () {
        test('wrestlers can be managed through their career lifecycle', function () {
            // Arrange - Using helper functions for realistic data
            $wrestler = createWrestler(['name' => 'John "The Rock" Stone']);
            
            // Assert - Using wrestling domain expectations
            expect($wrestler)->toHaveWrestlingName();
            expect($wrestler)->toHaveRealisticHometown();
            expect($wrestler)->toHaveValidPhysicalAttributes();
            expect($wrestler)->toExistInDatabase();
            
            // Act - Employ the wrestler
            $wrestler = createEmployedWrestler(['name' => 'John "The Rock" Stone']);
            
            // Assert - Employment status validation
            expect($wrestler)->toBeEmployed();
            expect($wrestler)->toBeAvailable();
            expect($wrestler)->toBeBookable();
            expect($wrestler)->not->toBeInjured();
            expect($wrestler)->not->toBeSuspended();
            expect($wrestler)->not->toBeRetired();
        });
        
        test('championship scenarios validate correctly', function () {
            // Arrange - Using helper to create complete championship scenario
            $championshipData = createChampionshipScenario('wrestler');
            
            // Assert - Championship validation using custom expectations
            expect($championshipData['title'])->toHaveWrestlingTitle();
            expect($championshipData['champion'])->toBeEmployed();
            expect($championshipData['champion'])->toBeChampion();
            expect($championshipData['champion'])->toHaveActiveChampionship();
            expect($championshipData['championship'])->toExistInDatabase();
        });
        
        test('stable membership works correctly', function () {
            // Arrange - Using helper to create complex stable scenario
            $stableData = createStableWithMembers(3, 1);
            
            // Assert - Stable and membership validation
            expect($stableData['stable'])->toHaveRealisticName();
            expect($stableData['stable'])->toExistInDatabase();
            expect($stableData['wrestlers'])->toHaveUniqueNames();
            expect($stableData['tag_teams'])->toHaveUniqueNames();
            
            // Verify each wrestler is in the stable
            foreach ($stableData['wrestlers'] as $wrestler) {
                expect($wrestler)->toBeInStable();
            }
        });
    });
    
    describe('Factory and Database Testing', function () {
        test('wrestler factory generates consistent realistic data', function () {
            // Arrange
            $factory = Wrestler::factory();
            
            // Assert - Factory validation using custom expectations
            expect($factory)->toGenerateRealisticData();
            expect($factory)->toCreateInDatabase();
            expect($factory)->toHaveConsistentStates();
            
            // Test multiple instances for consistency
            $wrestlers = createWrestlers(5);
            expect($wrestlers)->toHaveUniqueNames();
            
            foreach ($wrestlers as $wrestler) {
                expect($wrestler)->toHaveRealisticHeight();
                expect($wrestler)->toHaveRealisticWeight();
                expect($wrestler)->toHaveRealisticHometown();
                expect($wrestler)->toHaveValidEmploymentStatus();
            }
        });
        
        test('manager factory creates realistic managers', function () {
            // Arrange
            $managers = createManagers(3);
            
            // Assert - Manager-specific validation
            expect($managers)->toHaveUniqueEmails();
            
            foreach ($managers as $manager) {
                expect($manager)->toHaveRealisticName();
                expect($manager)->toExistInDatabase();
            }
        });
        
        test('seeders populate database correctly', function () {
            // Assert - Seeder validation using custom expectations
            expect('MatchTypesTableSeeder')->toSeedSuccessfully();
            expect('MatchDecisionsTableSeeder')->toSeedSuccessfully();
            expect('StatesTableSeeder')->toSeedSuccessfully();
            
            // Verify seeded data uniqueness
            $matchTypes = \App\Models\Matches\MatchType::all();
            expect($matchTypes)->toHaveUniqueNames();
        });
    });
    
    describe('Model Structure and Configuration Testing', function () {
        test('wrestler model has correct structure', function () {
            // Arrange
            $wrestler = new Wrestler();
            
            // Assert - Model structure validation using custom expectations
            expect($wrestler)->toHaveCorrectTable('wrestlers');
            expect($wrestler)->toHaveCorrectFillable([
                'name', 'height', 'weight', 'hometown', 'signature_move', 'status'
            ]);
            expect($wrestler)->toHaveCustomBuilder(\App\Builders\Roster\WrestlerBuilder::class);
            expect($wrestler)->toHaveFactory();
            expect($wrestler)->toHaveWorkingFactory();
        });
        
        test('title model implements wrestling business logic', function () {
            // Arrange
            $title = new Title();
            
            // Assert - Title-specific validation
            expect($title)->toHaveCorrectTable('titles');
            expect($title)->toImplementInterface('App\Models\Contracts\Retirable');
            expect($title)->usesTrait(\App\Models\Concerns\IsRetirable::class);
            
            // Test with factory data
            $titleWithData = Title::factory()->make();
            expect($titleWithData)->toHaveWrestlingTitle();
        });
        
        test('relationships are configured correctly', function () {
            // Arrange
            $wrestler = new Wrestler();
            
            // Assert - Relationship validation using custom expectations
            expect($wrestler)->toHaveBelongsToManyRelationship('stables');
            expect($wrestler)->toHaveBelongsToManyRelationship('tagTeams');
            expect($wrestler)->toHaveBelongsToManyRelationship('managers');
            expect($wrestler)->toHaveBelongsToManyRelationship('championships');
        });
    });
    
    describe('Time-Based and Status Testing', function () {
        test('employment timeline validation works correctly', function () {
            // Arrange - Using helper to create wrestler with employment history
            $wrestler = createWrestlerWithEmploymentHistory();
            
            // Assert - Timeline validation using custom expectations
            expect($wrestler->currentEmployment)->toHaveActiveTimelinePeriod();
            expect($wrestler->currentEmployment)->toHaveValidDateRange();
            
            // Test employment status
            expect($wrestler)->toBeEmployed();
            expect($wrestler)->toBeAvailable();
        });
        
        test('injury and suspension scenarios work correctly', function () {
            // Arrange - Using status-specific helpers
            $injuredWrestler = createInjuredWrestler();
            $suspendedWrestler = createSuspendedWrestler();
            $retiredWrestler = createRetiredWrestler();
            
            // Assert - Status-specific validation
            expect($injuredWrestler)->toBeInjured();
            expect($injuredWrestler)->not->toBeAvailable();
            expect($injuredWrestler)->not->toBeBookable();
            
            expect($suspendedWrestler)->toBeSuspended();
            expect($suspendedWrestler)->not->toBeAvailable();
            expect($suspendedWrestler)->not->toBeBookable();
            
            expect($retiredWrestler)->toBeRetired();
            expect($retiredWrestler)->not->toBeAvailable();
            expect($retiredWrestler)->not->toBeBookable();
        });
        
        test('wrestling date helpers create realistic dates', function () {
            // Arrange - Using time-based helpers
            $recentDate = wrestlingDate('recent');
            $pastDate = wrestlingDate('past');
            $futureDate = wrestlingDate('future');
            $historicalDate = wrestlingDate('historical');
            
            // Assert - Date validation
            expect($recentDate)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($pastDate)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($futureDate)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($historicalDate)->toBeInstanceOf(\Carbon\Carbon::class);
            
            // Verify date relationships
            expect($recentDate->isBefore(now()))->toBeTrue();
            expect($pastDate->isBefore($recentDate))->toBeTrue();
            expect($futureDate->isAfter(now()))->toBeTrue();
            expect($historicalDate->isBefore($pastDate))->toBeTrue();
        });
    });
    
    describe('Event and Match Testing', function () {
        test('event with matches scenario works correctly', function () {
            // Arrange - Using complex scenario helper
            $eventData = createEventWithMatches(3);
            
            // Assert - Event and match validation
            expect($eventData['event'])->toExistInDatabase();
            expect($eventData['matches'])->toHaveCount(3);
            
            foreach ($eventData['matches'] as $match) {
                expect($match)->toExistInDatabase();
            }
        });
        
        test('full roster creation works correctly', function () {
            // Arrange - Using comprehensive roster helper
            $roster = createFullRoster(20);
            
            // Assert - Roster validation
            expect($roster)->toHaveKey('wrestlers');
            expect($roster)->toHaveKey('managers');
            expect($roster)->toHaveKey('referees');
            expect($roster)->toHaveKey('tag_teams');
            expect($roster)->toHaveKey('stables');
            
            // Verify data quality
            expect($roster['wrestlers'])->toHaveUniqueNames();
            expect($roster['managers'])->toHaveUniqueEmails();
            expect($roster['tag_teams'])->toHaveUniqueNames();
            expect($roster['stables'])->toHaveUniqueNames();
        });
    });
});