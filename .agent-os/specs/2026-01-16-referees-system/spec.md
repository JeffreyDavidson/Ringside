# Spec Requirements Document

> Spec: Referees System Feature Documentation
> Created: 2026-01-16

## Overview

Document the Referees System—roster entities representing match officials in Ringside. Referees **officiate matches** but do not compete. Unlike managers, referees ARE bookable (for officiating). This spec serves as the authoritative reference for referee profiles, employment tracking, availability states (injuries, suspensions, retirements), and match officiating relationships.

## User Stories

### Referee Profile Management

As a **wrestling promoter**, I want to manage my roster of referees, so that I can track officials available for matches.

**Workflow:**
1. Create referee with profile (first name, last name)
2. Hire referee (create employment)
3. Track referee through their career
4. Release or retire referee when appropriate

### Employment Tracking

As a **promoter**, I want to track referee employment status, so that I know who is currently under contract.

**Workflow:**
1. Hire referee with start date
2. Track current and future employments
3. Release referee (end employment)
4. View employment history

### Availability Management

As a **promoter**, I want to track injuries, suspensions, and retirements, so that I know which referees are available for matches.

**Workflow:**
1. Record injury when referee is hurt
2. Clear injury when referee recovers
3. Suspend referee for disciplinary reasons
4. Lift suspension when served
5. Retire referee when career ends
6. Unretire for comebacks (rare)

### Match Officiating

As a **promoter**, I want to assign referees to matches, so that matches have proper officiating.

**Workflow:**
1. Check referee availability (employed, not injured, not suspended)
2. Assign bookable referees to matches
3. Track officiating history per referee

## Spec Scope

1. **Referee Entity** - Core model with profile attributes
2. **Employment** - Hire/release lifecycle with temporal tracking
3. **Injuries** - Injury tracking with recovery dates
4. **Suspensions** - Suspension tracking with lift dates
5. **Retirements** - Retirement tracking with comeback support
6. **Bookability** - Rules for match officiating eligibility
7. **Match Officiating** - Relationship to matches

## Out of Scope

- Match creation (see Match System spec)
- Event management (see Events System spec)
- Wrestler/Tag Team management (referees don't manage or compete)

## Expected Deliverable

1. Complete referee entity documentation with attributes
2. Employment lifecycle documentation
3. Availability state documentation (injuries, suspensions, retirements)
4. Bookability rules documentation
5. Match officiating relationship documentation
6. Query method reference

## Key Distinctions

**Referees vs Wrestlers:**
- Referees **officiate** matches; wrestlers **compete** in matches
- Referees implement `BookableOfficial`; wrestlers implement `BookableCompetitor`
- Referees use `OfficiatesMatches` trait; wrestlers use `IsBookableCompetitor` trait

**Referees vs Managers:**
- Referees ARE bookable (for officiating); managers are NOT bookable
- Referees have `isBookable()` method and `bookable()` scope
- Referees cannot be managed; managers manage others
