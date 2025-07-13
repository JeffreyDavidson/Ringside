# Match Creation Integration Testing Guide

## Overview

This guide documents the comprehensive integration testing approach for match creation workflows in the Ringside wrestling management system. These tests validate complex multi-entity interactions, business rule enforcement, and data integrity across the entire match creation pipeline.

## Match Creation Workflow Architecture

### Core Actions Hierarchy

```
AddMatchForEventAction (Master Orchestrator)
├── AddRefereesToMatchAction (Officiating Assignment)
├── AddTitlesToMatchAction (Championship Stakes)
└── AddCompetitorsToMatchAction (Competitor Management)
    ├── AddWrestlersToMatchAction (Individual Wrestlers)
    └── AddTagTeamsToMatchAction (Tag Team Assignment)
```

### Business Logic Flow

1. **Event Validation**: Verify event eligibility for new matches
2. **Base Match Creation**: Create match record with proper numbering
3. **Referee Assignment**: Assign qualified, available referees
4. **Title Assignment**: Add championship stakes with validation
5. **Competitor Assignment**: Add wrestlers/tag teams to match sides
6. **Validation & Rollback**: Ensure all business rules are satisfied

## Critical Integration Test Scenarios

### 1. End-to-End Match Creation Workflows

#### Complete Match Creation
- **Purpose**: Validate entire workflow from event to finished match
- **Scope**: All actions, validations, and database operations
- **Key Validations**: Transaction integrity, business rule compliance

#### Championship Match Creation
- **Purpose**: Test title match creation with current champions
- **Scope**: Title validation, champion participation, title type compatibility
- **Key Validations**: Champion eligibility, title availability, match type compatibility

#### Multi-Competitor Match Creation
- **Purpose**: Test complex matches with multiple wrestlers and tag teams
- **Scope**: Mixed competitor types, side assignment, availability checking
- **Key Validations**: Side distribution, competitor type compatibility

### 2. Availability Validation Integration

#### Wrestler Availability Testing
```php
// Real-world availability scenarios
test('wrestler availability validation across multiple systems')
- Employment status checking
- Injury status integration
- Suspension status validation
- Retirement status verification
- Event date conflict detection
```

#### Tag Team Availability Testing
```php
// Complex tag team validation
test('tag team availability depends on member availability')
- Both wrestlers must be available
- Team employment status validation
- Member injury/suspension impact
- Team-level suspension handling
```

#### Title Availability Testing
```php
// Championship availability validation
test('title availability for championship matches')
- Title active status verification
- Current champion participation
- Title type compatibility
- Multiple defense prevention
```

### 3. Business Rule Enforcement

#### Competitor Assignment Rules
- **Side Distribution**: Minimum 2 sides with competitors
- **Duplicate Prevention**: Same competitor cannot be on multiple sides
- **Type Compatibility**: Match types must support competitor types
- **Availability Requirements**: All competitors must be bookable

#### Championship Match Rules
- **Champion Participation**: Current champions must participate in title matches
- **Title Type Matching**: Singles titles only for individual competitors
- **Defense Limitations**: Titles cannot be defended multiple times per event
- **Vacancy Handling**: Special rules for vacant title matches

#### Referee Assignment Rules
- **Qualification Validation**: Referees must be qualified for match type
- **Availability Requirements**: Referees must be employed and available
- **Conflict Prevention**: No conflicts of interest allowed
- **Double-booking Prevention**: Referees cannot officiate multiple matches simultaneously

### 4. Complex Validation Scenarios

#### Match Type Compatibility
```php
// Match type validation matrix
Singles Match:
- Only individual wrestlers
- Singles titles only
- Standard referee requirements

Tag Team Match:
- Tag teams required
- Tag team titles only
- Specialized referee requirements

Royal Rumble:
- Only individual wrestlers
- Multiple competitors per side
- Special officiating requirements

Battle Royal:
- Mixed competitor types allowed
- Special elimination rules
- Multiple referee requirements
```

#### Event Scheduling Integration
```php
// Event-level validation
test('event scheduling constraint validation')
- Event date conflict detection
- Venue capacity constraints
- Match numbering sequence
- Event status validation
```

## Test Architecture

### Test Organization Structure

```
tests/Integration/Actions/EventMatches/
├── Workflows/
│   ├── CompleteMatchCreationWorkflowTest.php
│   ├── ChampionshipMatchWorkflowTest.php
│   ├── MultiCompetitorMatchWorkflowTest.php
│   └── SpecialMatchTypeWorkflowTest.php
├── Validation/
│   ├── CompetitorAvailabilityValidationTest.php
│   ├── TitleMatchValidationTest.php
│   ├── RefereeAssignmentValidationTest.php
│   └── MatchTypeCompatibilityTest.php
├── EdgeCases/
│   ├── ConflictDetectionTest.php
│   ├── ErrorRecoveryTest.php
│   ├── BusinessRuleViolationTest.php
│   └── TransactionRollbackTest.php
└── Performance/
    ├── LargeMatchCreationTest.php
    ├── ConcurrentMatchCreationTest.php
    └── DatabaseConstraintTest.php
```

### Test Data Setup Strategy

#### Factory Configuration
```php
// Realistic test data setup
createMatchCreationScenario():
- Event with proper scheduling
- Available wrestlers with employment
- Active tag teams with member availability
- Qualified referees with scheduling
- Active titles with current champions
```

#### Database State Management
```php
// Consistent test isolation
beforeEach():
- Clean database state
- Seed required reference data
- Create test entities with proper relationships
- Establish baseline availability states
```

### Critical Test Scenarios

#### 1. Complete Match Creation Workflow
```php
test('complete match creation workflow with all components')
- Create event with scheduling
- Add qualified referees
- Add active titles with champions
- Add available competitors
- Verify transaction integrity
- Validate business rule compliance
```

#### 2. Complex Availability Validation
```php
test('wrestler availability validation across systems')
- Employment status integration
- Injury status checking
- Suspension status validation
- Date conflict detection
- Retirement status verification
```

#### 3. Championship Match Scenarios
```php
test('championship match creation with title validation')
- Title availability verification
- Champion participation validation
- Title type compatibility checking
- Multiple defense prevention
- Vacant title handling
```

#### 4. Tag Team Match Complexity
```php
test('tag team match with member availability validation')
- Tag team employment status
- Individual wrestler availability
- Member injury/suspension impact
- Team-level validation
- Side assignment accuracy
```

#### 5. Multi-Title Championship Scenarios
```php
test('multiple title match creation and validation')
- Multiple active titles
- Champion participation requirements
- Title type compatibility
- Defense scheduling conflicts
- Tournament scenarios
```

#### 6. Error Handling and Recovery
```php
test('match creation error handling and rollback')
- Partial failure scenarios
- Transaction rollback validation
- Data consistency verification
- Error message accuracy
- Recovery procedures
```

## Business Rule Validation

### Competitor Eligibility Rules

1. **Employment Status**:
   - Must be currently employed
   - Cannot be released or terminated
   - Employment must be active on event date

2. **Health Status**:
   - Cannot be injured
   - Cannot be suspended
   - Cannot be retired

3. **Availability Status**:
   - Cannot be double-booked
   - Must be available on event date
   - Cannot have scheduling conflicts

### Championship Match Rules

1. **Title Requirements**:
   - Title must be active
   - Title cannot be retired or pulled
   - Title type must match competitor types

2. **Champion Requirements**:
   - Current champion must participate
   - Champion must be available and eligible
   - Champion cannot defend multiple titles simultaneously

3. **Match Type Requirements**:
   - Singles titles require individual competitors
   - Tag team titles require tag team competitors
   - Match type must support title type

### Referee Assignment Rules

1. **Qualification Requirements**:
   - Must be qualified for match type
   - Must meet experience requirements
   - Cannot have conflicts of interest

2. **Availability Requirements**:
   - Must be employed and active
   - Cannot be injured or suspended
   - Cannot be double-booked

## Edge Cases and Error Scenarios

### 1. Competitor Conflicts

#### Availability Conflicts
- Wrestler becomes injured between assignment and event
- Tag team member becomes unavailable
- Employment status changes after assignment
- Scheduling conflicts arise

#### Assignment Conflicts
- Same wrestler assigned to multiple sides
- Tag team member also assigned individually
- Overlapping tag team memberships
- Competitor type incompatibility

### 2. Championship Conflicts

#### Title Conflicts
- Current champion not participating
- Title type mismatch with competitors
- Multiple title defenses scheduled
- Title becomes inactive after assignment

#### Champion Conflicts
- Champion becomes unavailable
- Champion loses title before event
- Champion injury or suspension
- Champion employment changes

### 3. Event Scheduling Conflicts

#### Date Conflicts
- Event date changes after match creation
- Venue availability issues
- Competitor scheduling conflicts
- Referee availability conflicts

#### Capacity Constraints
- Event reaches match capacity
- Venue capacity limitations
- Resource allocation conflicts
- Equipment availability issues

## Data Integrity Validation

### Database Constraints

1. **Foreign Key Integrity**:
   - All relationships must be valid
   - Cascade deletes properly handled
   - Orphaned records prevented

2. **Business Rule Constraints**:
   - Unique constraints enforced
   - Check constraints validated
   - Trigger constraints executed

3. **Transaction Boundaries**:
   - Atomic operations ensured
   - Rollback scenarios tested
   - Consistency maintained

### Referential Integrity

1. **Match Relationships**:
   - Event relationship maintained
   - Match type compatibility verified
   - Stipulation relationships valid

2. **Competitor Relationships**:
   - Wrestler relationships accurate
   - Tag team relationships maintained
   - Side assignments consistent

3. **Championship Relationships**:
   - Title relationships valid
   - Champion relationships accurate
   - Match result relationships prepared

## Performance Considerations

### Large Dataset Testing

1. **Bulk Operations**:
   - Performance with many competitors
   - Large event match creation
   - Complex tournament scenarios

2. **Concurrent Operations**:
   - Multiple simultaneous match creation
   - Resource contention handling
   - Locking behavior validation

### Memory and Resource Management

1. **Memory Usage**:
   - Large competitor lists
   - Complex relationship loading
   - Query optimization validation

2. **Database Performance**:
   - Query execution time monitoring
   - Index usage verification
   - Connection pool management

## Integration Test Maintenance

### Test Data Management

1. **Factory Maintenance**:
   - Keep factory states current
   - Validate factory relationships
   - Ensure realistic test data

2. **Database Cleanup**:
   - Proper test isolation
   - State management between tests
   - Performance optimization

### Test Coverage Monitoring

1. **Coverage Metrics**:
   - Business logic coverage
   - Edge case coverage
   - Integration point coverage

2. **Quality Metrics**:
   - Test execution time
   - Failure rate monitoring
   - Maintenance overhead

## Conclusion

Comprehensive integration testing of match creation workflows is essential for maintaining system reliability and ensuring business rule compliance. These tests provide validation of complex multi-entity interactions, availability checking, and championship management that form the core of the wrestling event management system.

The integration tests serve as:
- **Business Rule Enforcement**: Validation of complex eligibility and availability rules
- **Data Integrity Assurance**: Verification of relationship consistency and constraints
- **Workflow Validation**: End-to-end process verification from creation to completion
- **Regression Prevention**: Protection against changes breaking complex interactions

Regular maintenance and enhancement of these integration tests ensure continued system reliability and business rule compliance across the match creation pipeline.