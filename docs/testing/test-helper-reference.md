# Test Helper Reference

This document provides a comprehensive reference for the test helper functions available in the Ringside test suite.

## Status Test Expectations

Located in `tests/Helpers/StatusTestExpectations.php`

### Employment Status Helpers

#### `expectEmploymentStatus(Employable $entity, EmploymentStatus $expectedStatus)`
Verifies an entity has a specific employment status.

```php
expectEmploymentStatus($wrestler, EmploymentStatus::Employed);
```

#### `expectStatusTransition(Employable $entity, EmploymentStatus $from, EmploymentStatus $to)`
Verifies a status transition occurred correctly.

```php
$wrestler = Wrestler::factory()->unemployed()->create();
EmployAction::run($wrestler, now());
expectStatusTransition($wrestler, EmploymentStatus::Unemployed, EmploymentStatus::Employed);
```

#### `expectToBeBookable($entity)`
Comprehensive check for booking availability.

```php
expectToBeBookable($wrestler);
// Checks: isEmployed(), isBookable(), !isNotInEmployment()
```

#### `expectToBeUnavailable($entity)`
Verifies entity cannot be booked.

```php
expectToBeUnavailable($wrestler);
// Checks: !isBookable()
```

### State Validation Helpers

#### `expectValidEmploymentLifecycle(Employable $entity)`
Validates employment record consistency.

```php
expectValidEmploymentLifecycle($wrestler);
// Verifies:
// - If employed: currentEmployment exists, started_at set, ended_at null
// - If not employed: no currentEmployment (unless future employment)
```

#### `expectValidRetirementState(Retirable $entity)`
Validates retirement record consistency.

```php
expectValidRetirementState($wrestler);
// Verifies:
// - If retired: currentRetirement exists, started_at set, ended_at null
// - If not retired: no currentRetirement
```

#### `expectValidInjuryState(Injurable $entity)`
Validates injury record consistency.

```php
expectValidInjuryState($wrestler);
```

#### `expectValidSuspensionState(Suspendable $entity)`
Validates suspension record consistency.

```php
expectValidSuspensionState($wrestler);
```

#### `expectValidEntityState($entity)`
Comprehensive state validation for all applicable contracts.

```php
expectValidEntityState($wrestler);
// Runs all applicable validations: employment, retirement, injury, suspension
```

### Championship Helpers

#### `expectValidChampionshipState($champion, $title)`
Validates championship relationship consistency.

```php
expectValidChampionshipState($wrestler, $title);
// Verifies:
// - If champion has title: title.currentChampionship points to champion
// - Championship record has no lost_at date
```

### Transaction Integrity

#### `expectTransactionIntegrity(callable $action, $entity)`
Verifies actions don't create orphaned records.

```php
expectTransactionIntegrity(
    fn() => EmployAction::run($wrestler, now()),
    $wrestler
);
// Checks that record counts only increase appropriately
```

### Future Employment

#### `expectValidFutureEmployment(Employable $entity)`
Validates future employment setup.

```php
expectValidFutureEmployment($wrestler);
// Verifies:
// - If has future employment: record exists, started_at > now, status = FutureEmployment
```

#### `expectStatusPriorityRules(Employable $entity)`
Validates status priority hierarchy.

```php
expectStatusPriorityRules($wrestler);
// Verifies: Retired > Employed > FutureEmployment > Released > Unemployed
```

## Integration Test Helpers

Located in `tests/Helpers/IntegrationTestHelpers.php`

### Lifecycle Scenarios

#### `createEmploymentLifecycleScenario(string $entityType = 'wrestler'): array`
Creates complete career lifecycle with predefined dates.

```php
$scenario = createEmploymentLifecycleScenario('wrestler');
// Returns:
// [
//     'entity' => Wrestler (unemployed),
//     'employment_date' => Carbon,
//     'injury_date' => Carbon (+6 months),
//     'release_date' => Carbon (+1 year),
//     'retirement_date' => Carbon (+2 years),
// ]
```

#### `createChampionshipStoryline(): array`
Creates title, champion, challenger scenario.

```php
$storyline = createChampionshipStoryline();
// Returns:
// [
//     'title' => Title (active),
//     'champion' => Wrestler (current champion),
//     'challenger' => Wrestler (bookable),
//     'championship' => TitleChampionship (current),
//     'title_change_date' => Carbon,
// ]
```

#### `createTitleLineage(int $reignCount = 5): array`
Creates multi-generational championship history.

```php
$lineage = createTitleLineage(5);
// Returns:
// [
//     'title' => Title,
//     'champions' => Collection<Wrestler>,
//     'championships' => Collection<TitleChampionship>,
//     'current_champion' => Wrestler,
// ]
```

### Complex Scenarios

#### `createStableLifecycleScenario(): array`
Creates stable with wrestlers and tag teams.

```php
$scenario = createStableLifecycleScenario();
// Returns stable with 4 wrestlers + 1 tag team
```

#### `createInjuryStoryline(): array`
Creates wrestler with championship who gets injured.

```php
$storyline = createInjuryStoryline();
// Returns champion wrestler with injury timeline
```

#### `createRetirementCeremonyScenario(): array`
Creates veteran wrestler with extensive career history.

```php
$ceremony = createRetirementCeremonyScenario();
// Returns wrestler with 10-year career, multiple titles
```

#### `createTournamentScenario(int $participantCount = 8): array`
Creates tournament with participants and winner.

```php
$tournament = createTournamentScenario(8);
// Returns tournament title, participants, winner
```

#### `createCompanyMergerScenario(): array`
Creates two companies with rosters and champions.

```php
$merger = createCompanyMergerScenario();
// Returns Company A & B rosters, titles, champions
```

### Setup and Cleanup

#### `setupRealisticTestState(): array`
Creates a realistic promotion roster.

```php
$roster = setupRealisticTestState();
// Returns:
// [
//     'active_wrestlers' => Collection<Wrestler> (20),
//     'injured_wrestlers' => Collection<Wrestler> (3),
//     'suspended_wrestlers' => Collection<Wrestler> (2),
//     'retired_wrestlers' => Collection<Wrestler> (5),
//     'managers' => Collection<Manager> (8),
//     'referees' => Collection<Referee> (5),
//     'tag_teams' => Collection<TagTeam> (6),
//     'active_titles' => Collection<Title> (5),
//     'inactive_titles' => Collection<Title> (2),
//     'championships' => Collection<TitleChampionship> (3),
// ]
```

#### `cleanupTestState(): void`
Cleans up all test data in dependency order.

```php
afterEach(function() {
    cleanupTestState();
});
```

## Existing Helpers (Enhanced)

Located in `tests/Helpers/TestHelpers.php`

### Enhanced Factory Helpers

#### `createWrestlerWithEmploymentHistory(): Wrestler`
Creates wrestler with past and current employment.

```php
$wrestler = createWrestlerWithEmploymentHistory();
// Has 2-year employment history
```

#### `createChampionshipScenario(string $championType = 'wrestler'): array`
Creates championship with event match context.

```php
$scenario = createChampionshipScenario('wrestler');
// Includes event and match for championship context
```

### Timeline Helpers

#### `wrestlingDate(string $period = 'recent'): Carbon`
Generates realistic wrestling dates.

```php
$date = wrestlingDate('historical'); // 1-10 years ago
$date = wrestlingDate('recent');     // 1-30 days ago
$date = wrestlingDate('future');     // 1-90 days ahead
```

#### `wrestlingTimePeriod(string $type = 'employment'): array`
Generates realistic time periods for different scenarios.

```php
$period = wrestlingTimePeriod('employment'); // 1-24 month employment
$period = wrestlingTimePeriod('injury');     // 1-12 week injury
$period = wrestlingTimePeriod('suspension'); // 1-6 month suspension
```

## Usage Examples

### Basic State Validation
```php
test('wrestler employment maintains valid state', function () {
    $wrestler = createWrestler();
    
    EmployAction::run($wrestler, now());
    
    expectValidEntityState($wrestler);
    expectToBeBookable($wrestler);
    expectValidEmploymentLifecycle($wrestler);
});
```

### Complex Scenario Testing
```php
test('championship storyline maintains data integrity', function () {
    $storyline = createChampionshipStoryline();
    
    expectValidChampionshipState(
        $storyline['champion'], 
        $storyline['title']
    );
    
    // Title change
    $storyline['championship']->update([
        'lost_at' => $storyline['title_change_date']
    ]);
    
    TitleChampionship::factory()
        ->for($storyline['title'])
        ->for($storyline['challenger'])
        ->current()
        ->create(['won_at' => $storyline['title_change_date']]);
    
    expectValidChampionshipState(
        $storyline['challenger'], 
        $storyline['title']
    );
});
```

### Transaction Integrity Testing
```php
test('action maintains transaction integrity', function () {
    $scenario = createEmploymentLifecycleScenario('wrestler');
    
    expectTransactionIntegrity(
        fn() => EmployAction::run(
            $scenario['entity'], 
            $scenario['employment_date']
        ),
        $scenario['entity']
    );
});
```