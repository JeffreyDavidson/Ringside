# Spec Requirements Document

> Spec: Match System Feature Documentation
> Created: 2026-01-16

## Overview

Document the Match System—the core competitive unit of Ringside events. Matches represent individual contests within events, connecting competitors (wrestlers and tag teams), referees (officials), championship titles, and results. This spec serves as the authoritative reference for match creation, competitor assignment, officiating, match types, stipulations, decisions, and result tracking.

## User Stories

### Match Creation

As a **wrestling promoter**, I want to create matches for events, so that I can build compelling match cards.

**Workflow:**
1. Select event to add match to
2. Choose match type (Singles, Tag Team, Triple Threat, etc.)
3. Optionally select match stipulation (Steel Cage, Ladder Match, etc.)
4. Add preview text for promotional purposes
5. Assign competitors to appropriate sides
6. Assign referee(s) to officiate

### Competitor Assignment

As a **promoter**, I want to assign wrestlers and tag teams to matches, so that I can create competitive contests.

**Workflow:**
1. Select match to add competitors to
2. Choose competitor type (wrestler or tag team)
3. Assign competitors to sides (side 1, side 2, etc.)
4. Validate competitors are bookable
5. Check for double-booking conflicts

### Match Officiating

As a **promoter**, I want to assign referees to matches, so that matches have proper officiating.

**Workflow:**
1. Select match to assign officials
2. Choose bookable referees
3. Assign one or more referees to the match
4. Referees can officiate multiple matches per event

### Title Matches

As a **promoter**, I want to put championships on the line in matches, so that titles can change hands.

**Workflow:**
1. Select match for title contest
2. Assign active championship title(s)
3. Ensure competitors are eligible challengers
4. Track title implications based on result

### Match Results

As a **promoter**, I want to record match outcomes, so that I can track wins, losses, and championship changes.

**Workflow:**
1. Select completed match
2. Choose match decision (Pinfall, Submission, DQ, etc.)
3. Record winners and losers
4. Handle special cases (draws, no contests)
5. Process title changes if applicable

## Spec Scope

1. **EventMatch Entity** - Core match model with type, stipulation, preview
2. **Match Types** - Enum defining Singles, Tag Team, Triple Threat, etc.
3. **Match Stipulations** - Special rules (Steel Cage, Ladder, No DQ, etc.)
4. **Competitors** - Polymorphic assignment of wrestlers and tag teams
5. **Side Assignment** - Grouping competitors by sides for team-based matches
6. **Officiating** - Referee assignment to matches
7. **Titles** - Championship title association
8. **Results** - Match outcomes with decisions, winners, and losers

## Out of Scope

- Event management (see Events System spec)
- Wrestler/Tag Team management (see respective specs)
- Referee management (see Referees System spec)
- Title management (see Titles System spec)
- Storyline/feud tracking

## Expected Deliverable

1. Complete match entity documentation with attributes
2. Match type enum documentation
3. Match decision enum documentation
4. Match stipulation documentation
5. Competitor assignment documentation (polymorphic, side-based)
6. Officiating relationship documentation
7. Result tracking documentation (winners/losers)
8. Action class reference for match operations

## Key Distinctions

**Matches vs Events:**
- Events contain multiple matches; matches belong to one event
- Events have dates and venues; matches have types and stipulations
- Events track status (scheduled, completed); matches track results (winners, losers)

**Competitors vs Officials:**
- Competitors (wrestlers, tag teams) compete in matches
- Officials (referees) officiate matches
- Both have bookability rules but different double-booking constraints
- Competitors: one match per event; Referees: multiple matches per event

**Match Results:**
- Results record the outcome of a match
- Winners and losers are tracked through the result
- Decisions determine how the match ended (Pinfall, Submission, etc.)
- Some decisions have no outcome (Time Limit Draw, No Decision)
