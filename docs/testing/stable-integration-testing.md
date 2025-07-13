# Stable Integration Testing Guide

## Overview

This guide documents the comprehensive integration testing approach for complex stable operations in the Ringside wrestling management system. These tests validate multi-entity interactions, database transactions, and cascading business logic that cannot be adequately tested through unit tests alone.

## Complex Stable Operations

### 1. MergeStablesAction

**Purpose**: Merges two stables by transferring all members from secondary to primary stable.

**Business Logic**:
- Transfers wrestlers, tag teams, and managers atomically
- Maintains proper date tracking for membership changes
- Deletes secondary stable after successful transfer
- Wrapped in database transaction for data integrity

**Integration Test Requirements**:
- Complete member transfer validation
- Polymorphic relationship handling
- Transaction rollback scenarios
- Historical data preservation

### 2. SplitStableAction

**Purpose**: Creates new stable and transfers specified members from original.

**Business Logic**:
- Selective member transfer by type
- New stable creation with valid data
- Original stable preservation with remaining members
- Comprehensive transaction management

**Integration Test Requirements**:
- Partial member transfer validation
- Multi-type member handling
- Business rule enforcement
- Stable creation validation

### 3. ReuniteAction

**Purpose**: Reactivates inactive stables for new storylines.

**Business Logic**:
- Status validation before reuniting
- Activity period creation
- Status transition management
- Business rule enforcement

**Integration Test Requirements**:
- Status transition validation
- Activity period management
- Historical data preservation
- Business rule compliance

## Test Architecture

### Test Organization

```
tests/Integration/Actions/Stables/
├── MergeStablesActionTest.php
├── SplitStableActionTest.php
├── ReuniteActionTest.php
├── StableMembershipOrchestratorTest.php
└── Complex/
    ├── MultiStableTransferTest.php
    ├── MemberRelationshipIntegrityTest.php
    └── TransactionRollbackTest.php
```

### Test Data Setup

**Factory States Required**:
- Stables with mixed member types
- Complex member relationships
- Historical membership data
- Status transition scenarios

**Test Helper Methods**:
```php
// Create stable with specified member configuration
createStableWithMembers(array $memberConfig): Stable

// Validate member relationships
assertMembershipIntegrity(Stable $stable): void

// Validate historical records
assertHistoricalDataPreservation(Stable $stable): void
```

### Critical Test Scenarios

#### MergeStablesAction Tests

```php
// Complete merger with all member types
test('merge stables transfers all members correctly')

// Empty stable merger
test('merge handles empty stables gracefully')

// Complex member relationships
test('merge preserves member relationship integrity')

// Transaction rollback scenarios
test('merge action handles transaction failures')
```

#### SplitStableAction Tests

```php
// Selective member splitting
test('split stable transfers specified members only')

// Multi-type member splitting
test('split handles mixed member types correctly')

// Minimum members validation
test('split respects minimum member requirements')

// New stable creation validation
test('split creates valid new stable with proper data')
```

#### ReuniteAction Tests

```php
// Status validation workflows
test('reunite validates stable status correctly')

// Activity period management
test('reunite creates proper activity periods')

// Historical data preservation
test('reunite preserves historical activity data')
```

## Business Rule Validation

### Critical Validations

1. **Status Transition Rules**:
   - Valid status transitions only
   - Business rule enforcement
   - Status synchronization

2. **Member Availability**:
   - Member employment status validation
   - Concurrent membership prevention
   - Availability date checking

3. **Minimum Member Requirements**:
   - Stable must maintain minimum members
   - Member count validation
   - Business rule compliance

4. **Date Sequence Validation**:
   - Proper chronological ordering
   - Historical data integrity
   - Membership period validation

## Database Transaction Testing

### Key Testing Patterns

1. **Successful Transaction Completion**:
   - All operations complete successfully
   - Data integrity maintained
   - Proper commit behavior

2. **Rollback Scenario Testing**:
   - Transaction failures handled gracefully
   - No partial state corruption
   - Proper rollback behavior

3. **Concurrent Operation Handling**:
   - Multiple operations don't interfere
   - Locking behavior validation
   - Race condition prevention

4. **Data Integrity Across Operations**:
   - Referential integrity maintained
   - No orphaned records
   - Consistent state preservation

## Edge Cases and Error Scenarios

### Common Edge Cases

1. **Empty Stables**:
   - Operations on stables with no members
   - Minimum member count validation
   - Graceful handling of empty states

2. **Overlapping Memberships**:
   - Concurrent membership attempts
   - Conflicting member assignments
   - Proper conflict resolution

3. **Date Conflicts**:
   - Invalid date sequences
   - Overlapping membership periods
   - Historical data conflicts

4. **Foreign Key Constraints**:
   - Cascade deletion scenarios
   - Referential integrity violations
   - Proper constraint handling

### Error Handling Validation

1. **Transaction Failures**:
   - Database constraint violations
   - Rollback behavior validation
   - Error message accuracy

2. **Business Rule Violations**:
   - Status transition errors
   - Member availability conflicts
   - Minimum count violations

3. **Data Validation Errors**:
   - Invalid input handling
   - Proper error responses
   - User-friendly error messages

## Performance Considerations

### Large Dataset Testing

1. **Bulk Member Operations**:
   - Performance with large member counts
   - Memory usage optimization
   - Query efficiency validation

2. **Historical Data Handling**:
   - Performance with extensive history
   - Query optimization validation
   - Memory management

### Concurrent Operation Testing

1. **Multi-User Scenarios**:
   - Concurrent stable operations
   - Locking behavior validation
   - Race condition prevention

2. **High-Load Testing**:
   - System behavior under load
   - Resource utilization monitoring
   - Performance degradation detection

## Integration Test Maintenance

### Test Data Management

1. **Factory States**:
   - Maintain complex factory states
   - Regular factory validation
   - Test data consistency

2. **Database Cleanup**:
   - Proper test isolation
   - Database state management
   - Performance optimization

### Test Coverage Monitoring

1. **Coverage Metrics**:
   - Integration test coverage tracking
   - Business logic coverage validation
   - Edge case coverage assessment

2. **Quality Metrics**:
   - Test execution time monitoring
   - Failure rate tracking
   - Maintenance overhead assessment

## Documentation Updates

### Required Documentation

1. **Business Rules Documentation**:
   - Update `docs/architecture/business-rules.md`
   - Add complex stable operation rules
   - Document member transfer constraints

2. **Architecture Documentation**:
   - Create `docs/architecture/stable-operations.md`
   - Document complex operation patterns
   - Add transaction management strategies

3. **Testing Documentation**:
   - Maintain this integration testing guide
   - Update test scenario matrices
   - Document edge case catalogs

## Conclusion

Comprehensive integration testing of complex stable operations is essential for maintaining system reliability and preventing data corruption. These tests provide validation of multi-entity interactions, database transactions, and cascading business logic that form the core of the stable management system.

The integration tests serve as:
- **Data Integrity Assurance**: Validation of complex member transfers
- **Business Rule Enforcement**: Verification of status transitions and constraints
- **Transaction Safety**: Validation of atomic operations and rollback scenarios
- **Regression Prevention**: Protection against future changes breaking complex workflows

Regular maintenance and enhancement of these integration tests ensure continued system reliability and business rule compliance.