# Ringside PRD (Lite)

> Condensed product context for AI development assistance

## What Is Ringside?

A **wrestling promotion management system** for professional wrestling promoters to manage rosters, events, matches, and championships.

## Core Entities

| Entity | Purpose | Key Rules |
|--------|---------|-----------|
| **Wrestler** | Individual performer | Employable, bookable, can join teams/stables |
| **Tag Team** | 2-wrestler team | Bookable when both members are bookable |
| **Manager** | Manages wrestlers/teams | NOT bookable, both parties must be employed |
| **Referee** | Officiates matches | Bookable when employed + healthy |
| **Stable** | Wrestling faction | Min 3 members for active status |
| **Event** | Wrestling show | Contains matches at a venue |
| **Match** | Competition | 13+ types, requires bookable competitors |
| **Title** | Championship belt | Singles or Tag Team type |

## Business Rules

**Status Computation** (never stored, always computed):
```
Priority: Retired > Employed > FutureEmployment > Released > Unemployed
```

**Bookability** = Employed + Not Injured + Not Suspended + Not Future-Dated

**Employment Relationships**: Manager-Wrestler/TagTeam requires both employed

## Match Types
Singles, Tag Team, Triple Threat, Fatal 4 Way, 6/8/10-Man Tag, Handicap (2v1, 3v2), Battle Royal, Royal Rumble, Tornado Tag, Gauntlet

## Tech Stack
- PHP 8.4 / Laravel 12 / Livewire 3 / Tailwind 4.1
- MySQL / Eloquent ORM
- Pest testing (100% coverage required)

## Current Status
- **Phase 0**: Complete (core platform)
- **Phase 1**: In planning (dashboard, mobile, analytics)

## Key Patterns
- Computed status (no stored status fields)
- Polymorphic relationships (wrestlers OR tag teams as champions/competitors)
- Temporal data (all employment/membership has date ranges)
- Soft deletes on all entities

---
*Full PRD: `.agent-os/product/prd.md` | Technical: `CLAUDE.md`*
