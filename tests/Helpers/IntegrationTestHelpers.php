<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Carbon;

/**
 * Integration test helper functions for complex scenarios.
 * 
 * These functions set up realistic test scenarios that mirror
 * real-world wrestling promotion operations.
 */

/**
 * Create a complete employment lifecycle scenario.
 */
function createEmploymentLifecycleScenario(string $entityType = 'wrestler'): array
{
    $entity = match ($entityType) {
        'wrestler' => Wrestler::factory()->unemployed()->create(),
        'manager' => Manager::factory()->unemployed()->create(),
        'referee' => Referee::factory()->unemployed()->create(),
        'tagteam' => TagTeam::factory()->unemployed()->create(),
        default => throw new InvalidArgumentException("Invalid entity type: {$entityType}"),
    };

    return [
        'entity' => $entity,
        'employment_date' => Carbon::now(),
        'injury_date' => Carbon::now()->addMonths(6),
        'release_date' => Carbon::now()->addYear(),
        'retirement_date' => Carbon::now()->addYears(2),
    ];
}

/**
 * Create a championship storyline scenario.
 */
function createChampionshipStoryline(): array
{
    $title = Title::factory()->active()->create(['name' => 'World Championship']);
    $champion = Wrestler::factory()->bookable()->create(['name' => 'Current Champion']);
    $challenger = Wrestler::factory()->bookable()->create(['name' => 'Challenger']);
    
    // Create current championship
    $championship = TitleChampionship::factory()
        ->for($title, 'title')
        ->for($champion, 'champion')
        ->current()
        ->create(['won_at' => Carbon::now()->subDays(100)]);

    return [
        'title' => $title,
        'champion' => $champion,
        'challenger' => $challenger,
        'championship' => $championship,
        'title_change_date' => Carbon::now(),
    ];
}

/**
 * Create a multi-generational title lineage.
 */
function createTitleLineage(int $reignCount = 5): array
{
    $title = Title::factory()->active()->create(['name' => 'Legacy Championship']);
    $champions = Wrestler::factory()->count($reignCount)->bookable()->create();
    $championships = [];

    foreach ($champions as $index => $champion) {
        $wonDate = Carbon::now()->subDays(365 - ($index * 60)); // ~2 month reigns
        $lostDate = $index < $reignCount - 1 ? Carbon::now()->subDays(305 - ($index * 60)) : null;

        $championships[] = TitleChampionship::factory()
            ->for($title, 'title')
            ->for($champion, 'champion')
            ->create([
                'won_at' => $wonDate,
                'lost_at' => $lostDate,
            ]);
    }

    return [
        'title' => $title,
        'champions' => $champions,
        'championships' => collect($championships),
        'current_champion' => $champions->last(),
    ];
}

/**
 * Create a stable formation and split scenario.
 */
function createStableLifecycleScenario(): array
{
    $wrestlers = Wrestler::factory()->count(4)->bookable()->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $stable = Stable::factory()->create(['name' => 'Test Stable']);

    // Add members to stable
    foreach ($wrestlers as $wrestler) {
        $stable->wrestlers()->attach($wrestler->id, ['joined_at' => Carbon::now()->subMonths(6)]);
    }
    
    $stable->tagTeams()->attach($tagTeam->id, ['joined_at' => Carbon::now()->subMonths(3)]);

    return [
        'stable' => $stable,
        'wrestlers' => $wrestlers,
        'tag_team' => $tagTeam,
        'split_date' => Carbon::now(),
        'formation_date' => Carbon::now()->subMonths(6),
    ];
}

/**
 * Create an injury storyline with recovery.
 */
function createInjuryStoryline(): array
{
    $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Injury Prone Wrestler']);
    $title = Title::factory()->active()->create(['name' => 'Championship At Risk']);
    
    // Create championship
    $championship = TitleChampionship::factory()
        ->for($title, 'title')
        ->for($wrestler, 'champion')
        ->current()
        ->create(['won_at' => Carbon::now()->subDays(200)]);

    return [
        'wrestler' => $wrestler,
        'title' => $title,
        'championship' => $championship,
        'injury_date' => Carbon::now(),
        'recovery_date' => Carbon::now()->addMonths(3),
        'return_date' => Carbon::now()->addMonths(4),
    ];
}

/**
 * Create a retirement ceremony scenario.
 */
function createRetirementCeremonyScenario(): array
{
    $legend = Wrestler::factory()->bookable()->create(['name' => 'Wrestling Legend']);
    
    // Create extensive career history
    $employments = [];
    for ($i = 0; $i < 3; $i++) {
        $startDate = Carbon::now()->subYears(10 - ($i * 3));
        $endDate = $i < 2 ? $startDate->copy()->addYears(2) : null;
        
        $employments[] = $legend->employments()->create([
            'started_at' => $startDate,
            'ended_at' => $endDate,
        ]);
    }

    // Create championship history
    $titles = Title::factory()->count(3)->active()->create();
    $championships = [];
    
    foreach ($titles as $index => $title) {
        $championships[] = TitleChampionship::factory()
            ->for($title, 'title')
            ->for($legend, 'champion')
            ->create([
                'won_at' => Carbon::now()->subYears(5 - $index),
                'lost_at' => Carbon::now()->subYears(4 - $index),
            ]);
    }

    return [
        'legend' => $legend,
        'employments' => collect($employments),
        'titles' => $titles,
        'championships' => collect($championships),
        'retirement_date' => Carbon::now(),
        'career_span_years' => 10,
    ];
}

/**
 * Create a tournament bracket scenario.
 */
function createTournamentScenario(int $participantCount = 8): array
{
    $participants = Wrestler::factory()->count($participantCount)->bookable()->create();
    $tournamentTitle = Title::factory()->active()->create(['name' => 'Tournament Championship']);
    
    // Create tournament matches (simplified)
    $rounds = ceil(log($participantCount, 2));
    $winner = $participants->first();

    // Winner becomes champion
    $championship = TitleChampionship::factory()
        ->for($tournamentTitle, 'title')
        ->for($winner, 'champion')
        ->current()
        ->create(['won_at' => Carbon::now()]);

    return [
        'title' => $tournamentTitle,
        'participants' => $participants,
        'winner' => $winner,
        'championship' => $championship,
        'tournament_date' => Carbon::now(),
        'rounds' => $rounds,
    ];
}

/**
 * Create a company merger scenario.
 */
function createCompanyMergerScenario(): array
{
    // Company A roster
    $companyAWrestlers = Wrestler::factory()->count(5)->bookable()->create();
    $companyATitle = Title::factory()->active()->create(['name' => 'Company A Championship']);
    $companyAChampion = $companyAWrestlers->first();
    
    // Company B roster  
    $companyBWrestlers = Wrestler::factory()->count(5)->bookable()->create();
    $companyBTitle = Title::factory()->active()->create(['name' => 'Company B Championship']);
    $companyBChampion = $companyBWrestlers->first();

    // Create championships
    $championshipA = TitleChampionship::factory()
        ->for($companyATitle, 'title')
        ->for($companyAChampion, 'champion')
        ->current()
        ->create(['won_at' => Carbon::now()->subDays(200)]);

    $championshipB = TitleChampionship::factory()
        ->for($companyBTitle, 'title')
        ->for($companyBChampion, 'champion')
        ->current()
        ->create(['won_at' => Carbon::now()->subDays(150)]);

    return [
        'company_a_wrestlers' => $companyAWrestlers,
        'company_a_title' => $companyATitle,
        'company_a_champion' => $companyAChampion,
        'company_a_championship' => $championshipA,
        'company_b_wrestlers' => $companyBWrestlers,
        'company_b_title' => $companyBTitle,
        'company_b_champion' => $companyBChampion,
        'company_b_championship' => $championshipB,
        'merger_date' => Carbon::now(),
        'unification_date' => Carbon::now()->addDays(30),
    ];
}

/**
 * Set up a realistic test database state.
 */
function setupRealisticTestState(): array
{
    $roster = [
        'active_wrestlers' => Wrestler::factory()->count(20)->bookable()->create(),
        'injured_wrestlers' => Wrestler::factory()->count(3)->injured()->create(),
        'suspended_wrestlers' => Wrestler::factory()->count(2)->suspended()->create(),
        'retired_wrestlers' => Wrestler::factory()->count(5)->retired()->create(),
        'managers' => Manager::factory()->count(8)->employed()->create(),
        'referees' => Referee::factory()->count(5)->employed()->create(),
        'tag_teams' => TagTeam::factory()->count(6)->employed()->create(),
        'active_titles' => Title::factory()->count(5)->active()->create(),
        'inactive_titles' => Title::factory()->count(2)->inactive()->create(),
    ];

    // Create some championships
    $championships = [];
    foreach ($roster['active_titles']->take(3) as $title) {
        $champion = $roster['active_wrestlers']->random();
        $championships[] = TitleChampionship::factory()
            ->for($title, 'title')
            ->for($champion, 'champion')
            ->current()
            ->create();
    }

    $roster['championships'] = collect($championships);
    
    return $roster;
}

/**
 * Create management relationship with proper pivot data.
 */
function createManagementRelationship($wrestler, $manager, array $options = []): void
{
    $defaultOptions = [
        'hired_at' => Carbon::now()->subMonths(6),
        'fired_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $pivotData = array_merge($defaultOptions, $options);
    $wrestler->managers()->attach($manager->id, $pivotData);
}

/**
 * Create tag team membership with proper pivot data.
 */
function createTagTeamMembership($wrestler, $tagTeam, array $options = []): void
{
    $defaultOptions = [
        'joined_at' => Carbon::now()->subMonths(6),
        'left_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $pivotData = array_merge($defaultOptions, $options);
    $wrestler->tagTeams()->attach($tagTeam->id, $pivotData);
}

/**
 * End management relationship by setting fired_at date.
 */
function endManagementRelationship($wrestler, $manager, ?Carbon $endDate = null): void
{
    $endDate = $endDate ?? Carbon::now();
    $wrestler->managers()->updateExistingPivot($manager->id, [
        'fired_at' => $endDate,
        'updated_at' => now(),
    ]);
}

/**
 * End tag team membership by setting left_at date.
 */
function endTagTeamMembership($wrestler, $tagTeam, ?Carbon $endDate = null): void
{
    $endDate = $endDate ?? Carbon::now();
    $wrestler->tagTeams()->updateExistingPivot($tagTeam->id, [
        'left_at' => $endDate,
        'updated_at' => now(),
    ]);
}

/**
 * Create multiple management periods for complex scenarios.
 */
function createManagementHistory($wrestler, array $periods): void
{
    foreach ($periods as $period) {
        createManagementRelationship($wrestler, $period['manager'], [
            'hired_at' => $period['hired_at'],
            'fired_at' => $period['fired_at'] ?? null,
        ]);
    }
}

/**
 * Create multiple tag team membership periods for complex scenarios.
 */
function createTagTeamHistory($wrestler, array $periods): void
{
    foreach ($periods as $period) {
        createTagTeamMembership($wrestler, $period['tag_team'], [
            'joined_at' => $period['joined_at'],
            'left_at' => $period['left_at'] ?? null,
        ]);
    }
}

/**
 * Create overlapping relationship periods for validation testing.
 */
function createOverlappingManagementPeriods($wrestler, $manager1, $manager2): array
{
    $firstPeriodStart = Carbon::now()->subYear();
    $firstPeriodEnd = Carbon::now()->subMonths(6);
    $secondPeriodStart = Carbon::now()->subMonths(8); // Overlaps

    createManagementRelationship($wrestler, $manager1, [
        'hired_at' => $firstPeriodStart,
        'fired_at' => $firstPeriodEnd,
    ]);

    createManagementRelationship($wrestler, $manager2, [
        'hired_at' => $secondPeriodStart,
        'fired_at' => Carbon::now()->subMonths(4),
    ]);

    return [
        'first_period_start' => $firstPeriodStart,
        'first_period_end' => $firstPeriodEnd,
        'second_period_start' => $secondPeriodStart,
        'overlap_detected' => $secondPeriodStart->between($firstPeriodStart, $firstPeriodEnd),
    ];
}

/**
 * Create complex relationship scenario with multiple periods and partners.
 */
function createComplexRelationshipScenario($wrestler): array
{
    $manager1 = Manager::factory()->employed()->create(['name' => 'First Manager']);
    $manager2 = Manager::factory()->employed()->create(['name' => 'Second Manager']);
    $tagTeam1 = TagTeam::factory()->employed()->create(['name' => 'First Tag Team']);
    $tagTeam2 = TagTeam::factory()->employed()->create(['name' => 'Second Tag Team']);

    // Management history
    createManagementHistory($wrestler, [
        [
            'manager' => $manager1,
            'hired_at' => Carbon::now()->subYear(),
            'fired_at' => Carbon::now()->subMonths(8),
        ],
        [
            'manager' => $manager2,
            'hired_at' => Carbon::now()->subMonths(3),
            'fired_at' => null, // Current
        ],
    ]);

    // Tag team history
    createTagTeamHistory($wrestler, [
        [
            'tag_team' => $tagTeam1,
            'joined_at' => Carbon::now()->subYear(),
            'left_at' => Carbon::now()->subMonths(6),
        ],
        [
            'tag_team' => $tagTeam2,
            'joined_at' => Carbon::now()->subMonths(3),
            'left_at' => null, // Current
        ],
    ]);

    return [
        'wrestler' => $wrestler,
        'managers' => [$manager1, $manager2],
        'tag_teams' => [$tagTeam1, $tagTeam2],
        'current_manager' => $manager2,
        'current_tag_team' => $tagTeam2,
        'previous_manager' => $manager1,
        'previous_tag_team' => $tagTeam1,
    ];
}

/**
 * Clean up test state completely.
 */
function cleanupTestState(): void
{
    // Clean up in dependency order
    TitleChampionship::query()->delete();
    Wrestler::query()->delete();
    Manager::query()->delete();
    Referee::query()->delete();
    TagTeam::query()->delete();
    Title::query()->delete();
}