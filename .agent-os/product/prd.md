# Ringside - Product Requirements Document

> Version: 1.0
> Last Updated: 2026-01-15
> Status: Phase 0 Complete, Phase 1 In Progress

---

## 1. Executive Summary

**Ringside** is a comprehensive wrestling promotion management system that empowers professional wrestling promoters to efficiently manage every aspect of their wrestling company operations. The platform provides unified roster management, intelligent match generation, championship tracking, and sophisticated business rule enforcement—enabling promoters to focus on creative storytelling while Ringside handles operational complexity.

### Key Value Propositions
- **Unified Operations**: Single platform for roster, events, matches, and championships
- **Business Intelligence**: Computed status system eliminating data inconsistencies
- **Temporal Accuracy**: Complete historical tracking with point-in-time calculations
- **Industry-Specific Logic**: Deep understanding of wrestling business rules and patterns

### Target Market
Independent wrestling promotions, regional wrestling companies, wrestling academies, and operations managers seeking professional-grade promotion management tools.

---

## 2. Problem Statement

### Industry Challenges

Wrestling promoters face complex operational challenges that generic business tools cannot address:

**Roster Management Complexity**
- Tracking employment status across multiple personnel types (wrestlers, managers, referees)
- Managing injuries, suspensions, and retirements with proper business logic
- Maintaining accurate career histories and employment relationships

**Event Planning Friction**
- Coordinating matches with complex eligibility rules
- Ensuring competitor availability (employment + health + suspension status)
- Managing 15+ different match types with specific requirements

**Championship Administration**
- Tracking title lineages and reign durations
- Managing championship succession and storyline continuity
- Maintaining championship prestige through accurate historical records

**Business Rule Enforcement**
- Wrestling industry has unique employment patterns and status hierarchies
- Manager-talent relationships have specific constraints
- Match eligibility depends on multiple computed factors

### Current Solutions Gap

Existing tools (spreadsheets, generic databases, manual tracking) fail wrestling promotions because they:
- Cannot compute status from employment/lifecycle data
- Don't understand wrestling-specific relationship rules
- Lack temporal tracking for historical accuracy
- Require manual enforcement of business logic

---

## 3. Target Users

### Primary Personas

| Persona | Profile | Key Needs |
|---------|---------|-----------|
| **Independent Promoter** | Solo operator, 1-3 shows/month | Streamlined roster tracking, simple event scheduling |
| **Regional Promotion Owner** | 10-50 talent, monthly events | Full employment management, championship system |
| **Operations Manager** | Day-to-day for larger companies | Regulatory compliance, reporting, talent utilization |
| **Wrestling Trainer** | Academy managing developmental talent | Student progress tracking, practice show booking |

### Secondary Personas

| Persona | Access Level | Use Case |
|---------|--------------|----------|
| **Wrestling Talent** | View-only | Personal career tracking, match history |
| **Fans/Media** | Public views | Championship histories, roster information |

### User Success Metrics
- 70% reduction in administrative overhead
- Elimination of roster status inconsistencies
- Ability to manage larger, more complex operations
- Improved championship and storyline tracking

---

## 4. Product Overview

### Core Domain Concepts

```
┌─────────────────────────────────────────────────────────────────┐
│                        RINGSIDE DOMAIN                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ROSTER                    EVENTS                 TITLES        │
│  ┌─────────┐              ┌─────────┐            ┌─────────┐   │
│  │Wrestlers│──┐           │  Event  │            │  Title  │   │
│  └─────────┘  │           └────┬────┘            └────┬────┘   │
│  ┌─────────┐  │                │                      │        │
│  │Tag Teams│──┼───────────►┌───┴───┐◄────────────────┘        │
│  └─────────┘  │            │ Match │                           │
│  ┌─────────┐  │            └───┬───┘                           │
│  │Managers │──┤                │                               │
│  └─────────┘  │           ┌────┴────┐                          │
│  ┌─────────┐  │           │ Result  │                          │
│  │Referees │──┘           └─────────┘                          │
│  └─────────┘                                                   │
│  ┌─────────┐              ┌─────────┐                          │
│  │ Stables │              │ Venues  │                          │
│  └─────────┘              └─────────┘                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### System Architecture

- **Backend**: PHP 8.4, Laravel 12, Eloquent ORM
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS 4.1
- **Database**: MySQL with sophisticated relationship schema
- **Testing**: Pest 4.0 with 100% coverage requirement
- **Quality**: PHPStan, Laravel Pint, Rector

---

## 5. Feature Specifications

### 5.1 Roster Management

#### Wrestlers
- **Attributes**: Name, height, weight, hometown, signature move
- **Employment**: Hire/fire dates with temporal history
- **Status**: Computed from employment data (Employed, Unemployed, Retired, Future Employment, Released)
- **Lifecycle**: Injuries, suspensions, retirements with date tracking
- **Relationships**: Can join tag teams, stables; can be managed

#### Tag Teams
- **Formation**: Exactly 2 wrestlers per team
- **Employment**: Independent tracking from individual wrestlers
- **Membership**: Join/leave dates with complete history
- **Bookability**: All current members must be individually bookable

#### Managers
- **Role**: Professional managers who guide wrestler/tag team careers
- **Relationships**: Can manage both wrestlers and tag teams simultaneously
- **Employment Rule**: Both manager AND managed entity must be employed
- **Note**: Managers are NOT bookable (don't participate in matches)

#### Referees
- **Role**: Officials who officiate matches
- **Employment**: Standard employment tracking with injuries/suspensions
- **Bookability**: Bookable when employed, healthy, and not suspended
- **Assignment**: Can be assigned to multiple matches per event

#### Stables
- **Definition**: Wrestling factions/groups
- **Membership**: Wrestlers and/or tag teams
- **Minimum**: 3 members required for active status
- **Lifecycle**: Formation, activity periods, disbandment

### 5.2 Event & Match Management

#### Events
- **Attributes**: Name, date, venue, preview content
- **Status**: Computed from date (Scheduled, Past, Unscheduled)
- **Structure**: Contains multiple matches organized by match number

#### Match Types (13+)
| Type | Competitors | Notes |
|------|-------------|-------|
| Singles | 2 | One-on-one |
| Tag Team | 2 teams | Standard tag match |
| Triple Threat | 3 | First to score wins |
| Triangle | 3 | Three-way dance |
| Fatal 4 Way | 4 | Four competitors |
| 6/8/10-Man Tag | 2 teams | Large team matches |
| Handicap | 2v1 or 3v2 | Uneven sides |
| Battle Royal | Many | Last one standing |
| Royal Rumble | 30 | Timed entries |
| Tornado Tag | 2 teams | All in ring simultaneously |
| Gauntlet | 2+ | Sequential matches |

#### Match Results
- **Decisions**: Pinfall, Submission, Count Out, Disqualification, Draw, No Decision
- **Tracking**: Winner(s), loser(s), decision type, championship implications

### 5.3 Championship System

#### Titles
- **Types**: Singles (individual wrestlers) or Tag Team
- **Lifecycle**: Undebuted → Pending Debut → Active → Inactive → Retired
- **Champion Types**: Polymorphic (wrestlers or tag teams can hold titles)

#### Championships (Reigns)
- **Tracking**: Won date, lost date, associated match
- **Duration**: Automatically calculated reign length
- **Lineage**: Complete championship history maintained

### 5.4 Business Rules Engine

#### Status Computation
All status fields are **computed, never stored**, eliminating data inconsistency:

```
Priority Hierarchy:
1. Retired (highest priority)
2. Employed
3. Future Employment
4. Released
5. Unemployed (default)
```

#### Bookability Rules
An entity is **bookable** when ALL conditions are met:
- Currently employed
- Not injured
- Not suspended
- Not awaiting future employment start date

#### Relationship Constraints
- Manager employment: Both parties must be employed
- Stable membership: Entity must be employed to join
- Match participation: Must be bookable + match type eligible

### 5.5 Venue Management

- **Attributes**: Name, address, city, state, zipcode
- **Event History**: Track all events held at venue
- **Usage**: Associate events with venues for location tracking

---

## 6. User Workflows

### Daily Promoter Workflow
1. **Dashboard Review**: Check upcoming events, roster alerts
2. **Roster Updates**: Process injuries, returns, employment changes
3. **Match Planning**: Build match cards for upcoming events
4. **Championship Management**: Update title situations as needed

### Event Creation Flow
1. Create event with name, date, venue
2. Add matches with type selection
3. Assign competitors (system validates bookability)
4. Assign referees (system validates availability)
5. Set championship stakes if applicable
6. Publish event

### Match Booking Flow
1. Select match type (UI adapts to type)
2. Choose competitors from bookable roster
3. System validates eligibility rules
4. Assign referee(s)
5. Optionally attach title defense
6. Save match to event

### Championship Management Flow
1. Create title with type (Singles/Tag Team)
2. Crown initial champion (via match or assignment)
3. Track defenses through match results
4. System maintains lineage automatically
5. Manage title status (activate, retire)

---

## 7. Technical Architecture

### Technology Stack
| Layer | Technology | Purpose |
|-------|------------|---------|
| Backend | PHP 8.4 | Server-side logic |
| Framework | Laravel 12 | Application structure |
| Frontend | Livewire 3 | Dynamic components |
| JS | Alpine.js | Client-side interactivity |
| CSS | Tailwind 4.1 | Styling |
| Database | MySQL | Data persistence |
| ORM | Eloquent | Database abstraction |
| Testing | Pest 4.0 | Test framework |

### Key Architectural Patterns

**Computed Status (Never Stored)**
```php
// Status derived from actual data
public function status(): EmploymentStatus
{
    if ($this->isRetired()) return EmploymentStatus::Retired;
    if ($this->isCurrentlyEmployed()) return EmploymentStatus::Employed;
    // ... priority chain
}
```

**Polymorphic Relationships**
- Match competitors can be wrestlers OR tag teams
- Champions can be wrestlers OR tag teams
- Managed entities can be wrestlers OR tag teams

**Temporal Data Management**
- All employment/membership uses date ranges
- Point-in-time queries supported
- Complete historical reconstruction possible

### Data Model Overview
- **Core Entities**: Promotion, User, Wrestler, TagTeam, Manager, Referee, Stable, Event, EventMatch, Title
- **Pivot Tables**: Employments, memberships, match competitors, championships
- **Soft Deletes**: All entities support soft deletion for data integrity
- **Multi-Tenant**: All roster/event entities belong to a Promotion

---

## 8. Roadmap

### Phase 0: Core Platform ✅ COMPLETE
- Wrestler, Tag Team, Manager, Referee management
- Event and Match system with 15+ match types
- Championship system with lineage tracking
- Stable/faction management
- Complete business rule enforcement
- 100% test coverage

### Phase 1: Design System, Multi-Tenant & User Experience (Current Priority)
- [ ] Design system (component library, design tokens, layouts)
- [ ] Promotion management (multi-tenant architecture)
- [ ] User system updates (roles, promotion ownership)
- [ ] Executive dashboard with metrics
- [ ] Mobile responsiveness
- [ ] UI/UX polish
- [ ] Reporting and analytics

### Phase 2: API & Integration
- [ ] RESTful API
- [ ] Webhooks
- [ ] Social media integration
- [ ] Calendar export

### Phase 3: Advanced Features
- [ ] Storyline management
- [ ] Financial/payroll tracking
- [ ] Contract management

### Phase 4: Cross-Promotion & Enterprise
- [ ] Cross-promotion events
- [ ] Talent sharing/loans
- [ ] Working agreements between promotions
- [ ] Team collaboration features

### Phase 5: Community
- [ ] Fan portal
- [ ] Voting system
- [ ] Media management

---

## 9. Success Metrics

### Quality Standards
| Metric | Target | Current |
|--------|--------|---------|
| Test Coverage | 100% | ✅ 100% |
| Production Bugs | Zero critical | ✅ Zero |
| Page Load Time | < 200ms | ✅ Met |
| Business Rule Coverage | 100% | ✅ 100% |

### Phase 1 Targets
- User satisfaction: > 4.5/5
- Mobile usage: > 60% of traffic
- Dashboard engagement: > 80% of users
- Performance: 30% improvement

### Long-term Goals
- Support 100+ wrestling promotions
- 50+ third-party API integrations
- 10,000+ active community users
- Industry standard for wrestling promotion management

---

## 10. Glossary

### Domain Terminology

| Term | Definition |
|------|------------|
| **Bookable** | An entity eligible to participate in matches (employed + healthy + not suspended) |
| **Stable** | A wrestling faction/group containing wrestlers and/or tag teams |
| **Reign** | A period during which a wrestler/team holds a championship |
| **Lineage** | The historical record of all championship holders |
| **Card** | The list of matches scheduled for an event |
| **Heel/Face** | Character alignment (villain/hero) - future feature |
| **Push** | Promotional focus on a wrestler - future feature |

### Status Definitions

| Status | Meaning |
|--------|---------|
| **Employed** | Currently under contract with active employment |
| **Unemployed** | No active or pending employment |
| **Future Employment** | Hired but start date is in the future |
| **Released** | Employment terminated (fired) |
| **Retired** | Voluntarily ended career |
| **Suspended** | Temporarily barred from competition |
| **Injured** | Cannot compete due to injury |

### Relationship Types

| Relationship | Entities | Description |
|--------------|----------|-------------|
| **Employment** | Manager ↔ Wrestler/TagTeam | Professional management relationship |
| **Membership** | Stable ↔ Wrestler/TagTeam | Faction membership with join/leave dates |
| **Partnership** | Wrestler ↔ TagTeam | Tag team membership with history |
| **Championship** | Title ↔ Wrestler/TagTeam | Title holder relationship with reign tracking |

---

*This document serves as the authoritative reference for Ringside product requirements. For technical implementation details, see `CLAUDE.md` and `docs/architecture/`.*
