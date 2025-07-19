# Business Rule Conventions

## Status Management

#### Employment Status
- **Employed**: Currently has active employment
- **Unemployed**: No current employment, available for hiring
- **Released**: Recently terminated employment
- **Future Employment**: Scheduled future employment

#### Activity Status
- **Active**: Currently participating in activities
- **Inactive**: Not currently participating
- **Suspended**: Temporarily barred from activities
- **Retired**: Permanently ended career

#### Injury Status
- **Healthy**: No current injuries
- **Injured**: Currently injured and unavailable
- **Recovering**: In recovery process
- **Cleared**: Recently cleared from injury

## Capability Matrix

#### Entity Capabilities
Different entities have different capabilities based on business rules:

| Entity | Employment | Injury | Suspension | Retirement | Booking | Debut |
|--------|------------|--------|------------|------------|---------|-------|
| Wrestler | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manager | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Referee | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| TagTeam | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ |
| Stable | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Title | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |

## Relationship Conventions

#### Membership Relationships
- **Stable Members**: Only Wrestlers and TagTeams can be direct members
- **Tag Team Members**: Only Wrestlers can be tag team members (one at a time)
- **Manager Relationships**: Managers can manage Wrestlers and TagTeams (multiple managers allowed simultaneously)
- **Championship Relationships**: Wrestlers and TagTeams can hold titles

#### Time-Based Relationships
- **Start/End Dates**: All relationships have start and optional end dates
- **Current vs Historical**: Distinguish current from historical relationships
- **Overlap Handling**: Handle overlapping relationship periods appropriately

## Manager Relationship Rules

#### Multiple Managers
- **Wrestlers**: Can have multiple managers simultaneously
- **TagTeams**: Can have multiple managers simultaneously
- **Business Rationale**: Real wrestling promotions often have wrestlers with multiple managers for different aspects (booking agent, personal trainer, storyline manager, etc.)

#### Manager Assignment
- **No Limit**: No maximum number of managers per wrestler/tag team
- **Independence**: Each manager relationship is independent with its own hire/fire dates
- **Multiple Periods**: Same manager can have multiple separate management periods with the same entity
- **Current Behavior**: Database allows duplicate manager assignments (same manager with multiple active periods)
