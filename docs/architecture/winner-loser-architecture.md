# Match Outcome Architecture

## Core model

A wrestling match is contested and won by sides. A side may contain one wrestler, one tag team, or multiple competitors when the match format calls for partners. Individual winner and loser records are not stored.

`EventMatch` owns the current outcome:

- `match_finish` records how the match ended with `MatchFinish`.
- `winning_side_id` identifies the winning `MatchSide` for decisive finishes.
- Draws and no-decisions record a finish without a winning side.
- Both fields are nullable while a match has no recorded outcome.

`MatchSide` makes the competition structure explicit:

- Each side belongs to exactly one `EventMatch`.
- `position` provides stable ordering within the match.
- A match cannot contain two sides at the same position.
- Each `MatchCompetitor` belongs to exactly one side and the same match.
- A competitor may appear only once in a match.

```text
EventMatch
├── match_finish
├── winning_side_id ───────────────┐
└── sides                          │
    ├── MatchSide (position 1) <───┘
    │   └── competitors
    │       └── MatchCompetitor ──morphTo──> Wrestler|TagTeam
    └── MatchSide (position 2)
        └── competitors
            └── MatchCompetitor ──morphTo──> Wrestler|TagTeam
```

## Recording an outcome

Use `RecordResultAction` to record the current outcome. The action locks the match and requested winning side, validates their relationship, and updates both outcome fields atomically.

A decisive `MatchFinish` requires a non-empty winning side belonging to the match. `TimeLimitDraw` and `NoDecision` prohibit a winning side. Callers do not calculate or persist loser rows: every non-winning side is understood to have lost when the finish is decisive.

## History and deletion

`EventMatch` is soft deleted, so its sides, competitors, and current outcome remain available as historical booking data. Result-revision history is a separate concern and must not be modeled as duplicated current winner and loser tables. If revisions are introduced, store immutable outcome-change records while keeping the current outcome on `EventMatch`.

## Querying

Eager-load `sides.competitors.competitor` when rendering the full match structure. Eager-load `winningSide.competitors.competitor` when rendering results. Store competitor morph types through `getMorphClass()` so persisted values remain consistent with the application's enforced morph map.
