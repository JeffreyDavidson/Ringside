# Spec Requirements Document

> Spec: Managers System Feature Documentation
> Created: 2026-01-16

## Overview

Document the Managers System—roster entities representing talent managers in Ringside. Managers represent wrestlers and tag teams but are **not bookable** for matches themselves. This spec serves as the authoritative reference for manager profiles, employment tracking, availability states (injuries, suspensions, retirements), and relationships with wrestlers and tag teams.

## User Stories

### Manager Profile Management

As a **wrestling promoter**, I want to manage my roster of managers, so that I can track talent representatives in my promotion.

**Workflow:**
1. Create manager with profile (first name, last name)
2. Hire manager (create employment)
3. Assign manager to wrestlers or tag teams
4. Track manager through their career
5. Release or retire manager when appropriate

### Employment Tracking

As a **promoter**, I want to track manager employment status, so that I know who is currently under contract.

**Workflow:**
1. Hire manager with start date
2. Track current and future employments
3. Release manager (end employment)
4. View employment history

### Availability Management

As a **promoter**, I want to track injuries, suspensions, and retirements, so that I know which managers are available for assignments.

**Workflow:**
1. Record injury when manager is hurt
2. Clear injury when manager recovers
3. Suspend manager for disciplinary reasons
4. Lift suspension when served
5. Retire manager when career ends
6. Unretire for comebacks (rare)

### Client Management

As a **promoter**, I want to assign managers to wrestlers and tag teams, so that I can build storylines around manager-talent relationships.

**Workflow:**
1. Hire manager to represent a wrestler
2. Hire manager to represent a tag team
3. Fire manager from a client relationship
4. Track management history

## Spec Scope

1. **Manager Entity** - Core model with profile attributes
2. **Employment** - Hire/release lifecycle with temporal tracking
3. **Injuries** - Injury tracking with recovery dates
4. **Suspensions** - Suspension tracking with lift dates
5. **Retirements** - Retirement tracking with comeback support
6. **Wrestler Relationships** - Managing wrestlers
7. **Tag Team Relationships** - Managing tag teams
8. **Stable Association** - Indirect membership through managed talent

## Out of Scope

- Wrestler management (see Wrestlers System spec)
- Tag team management (see Tag Teams System spec)
- Stable management (see Stables System spec)
- Match creation (Managers are not bookable)

## Expected Deliverable

1. Complete manager entity documentation with attributes
2. Employment lifecycle documentation
3. Availability state documentation (injuries, suspensions, retirements)
4. Client relationship documentation (wrestlers, tag teams)
5. Stable association documentation (indirect)
6. Query method reference

## Key Distinction

**Managers are NOT bookable.** Unlike wrestlers and tag teams, managers do not participate in matches. They exist to represent talent and build storylines. The Manager model does not implement `BookableCompetitor` and the `ManagerBuilder` has no booking scopes.
