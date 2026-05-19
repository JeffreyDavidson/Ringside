# Match Types Reference

> Match System
> Reference: @.agent-os/specs/2026-01-16-match-system/spec.md

---

## Overview

Ringside supports 14 distinct match types, each with specific rules for competitor counts, side assignments, and allowed participant types. This document details each match type's configuration.

---

## Standard Matches

### Singles
One-on-one competition between two wrestlers.

| Property | Value |
|----------|-------|
| Enum | `MatchType::Singles` |
| Value | `'singles'` |
| Sides | 2 |
| Competitors per Side | 1 |
| Allowed Types | Wrestler only |

```
Side 1: [Wrestler A]
  vs
Side 2: [Wrestler B]
```

---

### Tag Team
Traditional 2-on-2 tag team match.

| Property | Value |
|----------|-------|
| Enum | `MatchType::TagTeam` |
| Value | `'tag-team'` |
| Sides | 2 |
| Competitors per Side | 2 wrestlers OR 1 tag team |
| Allowed Types | Wrestler, TagTeam |

```
Side 1: [Tag Team A] OR [Wrestler A + Wrestler B]
  vs
Side 2: [Tag Team B] OR [Wrestler C + Wrestler D]
```

---

### Tornado Tag Team
Tag team match where all participants are legal simultaneously.

| Property | Value |
|----------|-------|
| Enum | `MatchType::TornadoTagTeam` |
| Value | `'tornado-tag-team'` |
| Sides | 2 |
| Competitors per Side | 2 wrestlers OR 1 tag team |
| Allowed Types | Wrestler, TagTeam |

```
Side 1: [Tag Team A] OR [Wrestler A + Wrestler B]
  vs
Side 2: [Tag Team B] OR [Wrestler C + Wrestler D]
```

---

## Multi-Man Tag Team Matches

### 6-Man Tag Team
Three-on-three tag team competition.

| Property | Value |
|----------|-------|
| Enum | `MatchType::SixManTagTeam` |
| Value | `'6-man-tag-team'` |
| Sides | 2 |
| Competitors per Side | 3 wrestlers OR tag team + wrestler |
| Allowed Types | Wrestler, TagTeam |

```
Side 1: [3 Wrestlers] OR [Tag Team + Wrestler]
  vs
Side 2: [3 Wrestlers] OR [Tag Team + Wrestler]
```

---

### 8-Man Tag Team
Four-on-four tag team competition.

| Property | Value |
|----------|-------|
| Enum | `MatchType::EightManTagTeam` |
| Value | `'8-man-tag-team'` |
| Sides | 2 |
| Competitors per Side | 4 wrestlers OR 2 tag teams |
| Allowed Types | Wrestler, TagTeam |

```
Side 1: [4 Wrestlers] OR [Tag Team A + Tag Team B]
  vs
Side 2: [4 Wrestlers] OR [Tag Team C + Tag Team D]
```

---

### 10-Man Tag Team
Five-on-five tag team competition.

| Property | Value |
|----------|-------|
| Enum | `MatchType::TenManTagTeam` |
| Value | `'10-man-tag-team'` |
| Sides | 2 |
| Competitors per Side | 5 wrestlers OR 2 tag teams + wrestler |
| Allowed Types | Wrestler, TagTeam |

```
Side 1: [5 Wrestlers] OR [2 Tag Teams + Wrestler]
  vs
Side 2: [5 Wrestlers] OR [2 Tag Teams + Wrestler]
```

---

## Multi-Way Matches

### Triple Threat
Three-way competition, first to score wins.

| Property | Value |
|----------|-------|
| Enum | `MatchType::TripleThreat` |
| Value | `'triple-threat'` |
| Sides | 3 |
| Competitors per Side | 1 |
| Allowed Types | Wrestler, TagTeam |

```
Side 1: [Competitor A]
  vs
Side 2: [Competitor B]
  vs
Side 3: [Competitor C]
```

---

### Triangle
Alternative name for three-way competition (often used for tag teams).

| Property | Value |
|----------|-------|
| Enum | `MatchType::Triangle` |
| Value | `'triangle'` |
| Sides | 3 |
| Competitors per Side | 1 tag team OR wrestler |
| Allowed Types | Wrestler, TagTeam |

---

### Fatal 4-Way
Four-way competition.

| Property | Value |
|----------|-------|
| Enum | `MatchType::Fatal4Way` |
| Value | `'fatal-4-way'` |
| Sides | 4 |
| Competitors per Side | 1 |
| Allowed Types | Wrestler, TagTeam |

```
Side 1: [Competitor A]
  vs
Side 2: [Competitor B]
  vs
Side 3: [Competitor C]
  vs
Side 4: [Competitor D]
```

---

## Handicap Matches

### Two-on-One Handicap
Two competitors against one.

| Property | Value |
|----------|-------|
| Enum | `MatchType::TwoOnOneHandicap` |
| Value | `'two-on-one-handicap'` |
| Sides | 2 |
| Side 1 Competitors | 1 wrestler |
| Side 2 Competitors | 2 wrestlers OR 1 tag team |
| Allowed Types | Wrestler only |

```
Side 1: [Wrestler A]
  vs
Side 2: [Wrestler B + Wrestler C]
```

---

### Three-on-Two Handicap
Three competitors against two.

| Property | Value |
|----------|-------|
| Enum | `MatchType::ThreeOnTwoHandicap` |
| Value | `'three-on-two-handicap'` |
| Sides | 2 |
| Side 1 Competitors | 2 wrestlers |
| Side 2 Competitors | 3 wrestlers |
| Allowed Types | Wrestler only |

```
Side 1: [Wrestler A + Wrestler B]
  vs
Side 2: [Wrestler C + Wrestler D + Wrestler E]
```

---

## Large-Scale Matches

### Battle Royal
Multiple competitors, last one standing wins. No fixed sides.

| Property | Value |
|----------|-------|
| Enum | `MatchType::BattleRoyal` |
| Value | `'battle-royal'` |
| Sides | null (free-for-all) |
| Minimum Competitors | 2 |
| Allowed Types | Wrestler, TagTeam |

```
All competitors: [A, B, C, D, E, F, ...]
  - No sides assigned
  - Elimination format
```

---

### Royal Rumble
Timed-entry battle royal format.

| Property | Value |
|----------|-------|
| Enum | `MatchType::RoyalRumble` |
| Value | `'royal-rumble'` |
| Sides | null (free-for-all) |
| Minimum Competitors | 2 |
| Allowed Types | Wrestler, TagTeam |

```
All competitors: [A, B, C, D, ...]
  - Timed entries
  - Over-the-top elimination
```

---

### Gauntlet
Sequential one-on-one matches, winner continues.

| Property | Value |
|----------|-------|
| Enum | `MatchType::Gauntlet` |
| Value | `'gauntlet'` |
| Sides | 2 |
| Format | Successive 1v1 matches |
| Allowed Types | Wrestler only |

```
Match 1: [Wrestler A] vs [Wrestler B]
  Winner faces:
Match 2: [Winner] vs [Wrestler C]
  Winner faces:
Match 3: [Winner] vs [Wrestler D]
  ...
```

---

## Match Type Summary Table

| Match Type | Sides | Min Competitors | Tag Teams Allowed |
|------------|-------|-----------------|-------------------|
| Singles | 2 | 2 | No |
| Tag Team | 2 | 2 | Yes |
| Tornado Tag | 2 | 2 | Yes |
| 6-Man Tag | 2 | 6 | Yes |
| 8-Man Tag | 2 | 8 | Yes |
| 10-Man Tag | 2 | 10 | Yes |
| Triple Threat | 3 | 3 | Yes |
| Triangle | 3 | 3 | Yes |
| Fatal 4-Way | 4 | 4 | Yes |
| 2-on-1 Handicap | 2 | 3 | No |
| 3-on-2 Handicap | 2 | 5 | No |
| Battle Royal | null | 2 | Yes |
| Royal Rumble | null | 2 | Yes |
| Gauntlet | 2 | 2+ | No |

---

## Decision Types by Match Format

| Decision | Standard | Multi-Way | Battle Royal |
|----------|----------|-----------|--------------|
| Pinfall | Yes | Yes | No |
| Submission | Yes | Yes | No |
| DQ | Yes | Varies | No |
| Countout | Yes | Varies | No |
| Knockout | Yes | Yes | No |
| Stipulation | Yes | Yes | Yes |
| Forfeit | Yes | Yes | Yes |
| Elimination | No | No | Yes |
