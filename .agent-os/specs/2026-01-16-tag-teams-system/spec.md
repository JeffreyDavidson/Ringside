# Spec Requirements Document

> Spec: Tag Teams System Feature Documentation
> Created: 2026-01-16

## Overview

Document the Tag Teams System—roster entities representing paired wrestling talent in Ringside. This spec serves as the authoritative reference for tag team management, including formation (wrestler partnerships), employment tracking, availability states (suspensions, retirements), relationships (managers, stables), and championship tracking.

## User Stories

### Tag Team Formation

As a **wrestling promoter**, I want to form tag teams from my roster of wrestlers, so that I can book them as a unit.

**Workflow:**
1. Select two wrestlers to form a tag team
2. Create tag team with name and optional signature move
3. Track wrestler partnerships over time
4. Replace partners when needed

### Employment Tracking

As a **promoter**, I want to track tag team employment status, so that I know which teams are under contract.

**Workflow:**
1. Hire tag team with start date
2. Track current and future employments
3. Release tag team (end employment)
4. View employment history

### Availability Management

As a **promoter**, I want to track suspensions and retirements, so that I know which teams are available for matches.

**Workflow:**
1. Suspend tag team for disciplinary reasons
2. Reinstate when suspension served
3. Retire tag team when career ends
4. Unretire for comebacks (rare)

> **Note:** Tag teams do not have injuries—individual wrestlers get injured. A tag team's bookability depends on their wrestlers' individual availability.

### Match Booking

As a **promoter**, I want to know which tag teams are bookable, so that I can create match cards.

**Workflow:**
1. Check tag team availability (employed, not suspended, all wrestlers bookable)
2. Assign bookable tag teams to matches
3. Track match history per team

### Relationship Management

As a **promoter**, I want to track tag team relationships, so that I can manage storylines.

**Workflow:**
1. Assign managers to tag teams
2. Add tag teams to stables
3. Track tag team championship reigns

## Spec Scope

1. **Tag Team Entity** - Core model with profile attributes
2. **Wrestler Partnerships** - Partner join/leave lifecycle
3. **Employment** - Hire/release lifecycle with temporal tracking
4. **Suspensions** - Suspension tracking with reinstatement dates
5. **Retirements** - Retirement tracking with comeback support
6. **Bookability** - Rules for match eligibility (includes partner requirements)
7. **Manager Relationships** - Being managed by managers
8. **Stable Membership** - Joining/leaving stables
9. **Championships** - Holding tag team titles

## Out of Scope

- Individual wrestler management (see Wrestlers System spec)
- Stable management (see Stables System spec)
- Manager management (see Managers System spec)
- Match creation (see Match System spec)
- Championship management (see Championship System spec)

## Expected Deliverable

1. Complete tag team entity documentation with attributes
2. Wrestler partnership documentation
3. Employment lifecycle documentation
4. Availability state documentation (suspensions, retirements)
5. Bookability rules documentation (including partner requirements)
6. Relationship documentation
7. Query method reference
