# Spec Requirements Document

> Spec: Wrestlers System Feature Documentation
> Created: 2026-01-16

## Overview

Document the Wrestlers System—the foundational roster entity in Ringside representing individual wrestling talent. This spec serves as the authoritative reference for wrestler management, including employment tracking, availability states (injuries, suspensions, retirements), relationships (managers, tag teams, stables), and championship tracking.

## User Stories

### Wrestler Management

As a **wrestling promoter**, I want to manage my roster of wrestlers, so that I can track all talent in my promotion.

**Workflow:**
1. Create wrestler with profile (name, height, weight, hometown, signature move)
2. Hire wrestler (create employment)
3. Track wrestler through their career
4. Release or retire wrestler when appropriate

### Employment Tracking

As a **promoter**, I want to track wrestler employment status, so that I know who is currently under contract.

**Workflow:**
1. Hire wrestler with start date
2. Track current and future employments
3. Release wrestler (end employment)
4. View employment history

### Availability Management

As a **promoter**, I want to track injuries, suspensions, and retirements, so that I know who is available for matches.

**Workflow:**
1. Record injury when wrestler is hurt
2. Clear injury when wrestler recovers
3. Suspend wrestler for disciplinary reasons
4. Lift suspension when served
5. Retire wrestler when career ends
6. Unretire for comebacks (rare)

### Match Booking

As a **promoter**, I want to know which wrestlers are bookable, so that I can create match cards.

**Workflow:**
1. Check wrestler availability (employed, not injured, not suspended)
2. Assign bookable wrestlers to matches
3. Track match history per wrestler

### Relationship Management

As a **promoter**, I want to track wrestler relationships, so that I can manage storylines.

**Workflow:**
1. Assign managers to wrestlers
2. Form tag teams with wrestlers
3. Add wrestlers to stables
4. Track championship reigns

## Spec Scope

1. **Wrestler Entity** - Core model with profile attributes
2. **Employment** - Hire/release lifecycle with temporal tracking
3. **Injuries** - Injury tracking with recovery dates
4. **Suspensions** - Suspension tracking with lift dates
5. **Retirements** - Retirement tracking with comeback support
6. **Bookability** - Rules for match eligibility
7. **Manager Relationships** - Being managed by managers
8. **Tag Team Membership** - Joining/leaving tag teams
9. **Stable Membership** - Joining/leaving stables
10. **Championships** - Holding singles titles

## Out of Scope

- Tag team management (see Tag Teams System spec)
- Stable management (see Stables System spec)
- Manager management (see Managers System spec)
- Match creation (see Match System spec)
- Championship management (see Championship System spec)

## Expected Deliverable

1. Complete wrestler entity documentation with attributes
2. Employment lifecycle documentation
3. Availability state documentation (injuries, suspensions, retirements)
4. Bookability rules documentation
5. Relationship documentation
6. Query method reference
