# Spec Requirements Document

> Spec: Titles System Feature Documentation
> Created: 2026-01-16

## Overview

Document the Titles System—the championship management system for wrestling promotions. Titles represent championships that can be competed for and defended in matches. Each title has an activity lifecycle (debut, pull, reinstate, retire), a type (singles or tag team), and tracks championship reigns. This spec serves as the authoritative reference for title lifecycle, championship tracking, and title-match relationships.

## User Stories

### Title Creation

As a **wrestling promoter**, I want to create championships for my promotion, so that I can feature title matches on events.

**Workflow:**
1. Create title with name and type (singles or tag team)
2. Optionally schedule a debut date
3. Title starts as "New" if no debut date, "Pending Debut" if future date, "Active" if current date

### Title Debut

As a **promoter**, I want to debut titles at events, so that I can introduce new championships.

**Workflow:**
1. Select a new title (never debuted)
2. Set debut date
3. Title becomes "Active" when debut date arrives
4. Title is eligible for championship matches

### Championship Management

As a **promoter**, I want to track championship reigns, so that I can maintain title history.

**Workflow:**
1. Champion wins title in a match
2. Championship reign is recorded with win date and match
3. Champion defends title or loses to challenger
4. All reigns tracked with win/loss dates and matches

### Title Lifecycle

As a **promoter**, I want to pull or retire titles, so that I can manage my championship roster.

**Workflow:**
1. **Pull**: Temporarily deactivate a title (can be reinstated)
2. **Retire**: Permanently end a title's lineage (can be unretired)
3. Retired titles end any current championship reign

### Title History

As a **promoter**, I want to view title history, so that I can see championship lineages.

**Workflow:**
1. View all championship reigns for a title
2. See current champion (if any)
3. View reign lengths and matches
4. Track first, previous, and longest reigns

## Spec Scope

1. **Title Entity** - Core model with name, type, computed status
2. **TitleChampionship Entity** - Championship reigns with polymorphic champion
3. **TitleActivityPeriod Entity** - Activity periods for debut/pull/reinstate tracking
4. **TitleRetirement Entity** - Retirement periods
5. **TitleStatusChange Entity** - Status change history
6. **Title Status** - Computed status (New, PendingDebut, Active, Inactive)
7. **Title Type** - Singles or TagTeam
8. **Query Scopes** - Filtering by status, activity, championship state

## Out of Scope

- Match management (see Match System spec)
- Event management (see Events System spec)
- Competitor management (see Wrestlers/Tag Teams System specs)

## Expected Deliverable

1. Complete title entity documentation with attributes
2. TitleChampionship entity documentation
3. TitleActivityPeriod entity documentation
4. TitleRetirement entity documentation
5. TitleStatus and TitleType enum documentation
6. Title lifecycle action reference
7. Query builder methods reference
8. Championship tracking relationships

## Key Distinctions

**Titles vs Championships:**
- Titles are the championship belts; championships are the reigns
- Titles have status and activity; championships have win/loss dates
- Titles are entities; championships are historical records

**Title Status:**
- Status is **computed**, never stored
- Based on activity periods:
  - **New**: No activity periods (never debuted)
  - **PendingDebut**: Future activity period scheduled
  - **Active**: Current activity period exists
  - **Inactive**: Previous activity periods exist but no current

**Activity vs Retirement:**
- Activity periods track active/inactive state (temporary)
- Retirement is a separate concern (more permanent)
- Retired titles can be unretired; pulled titles can be reinstated

**Title Types:**
- Singles titles can only be held by Wrestlers
- Tag Team titles can only be held by TagTeams
- Type determines eligible champion entity
