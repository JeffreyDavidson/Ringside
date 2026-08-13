# Winner and Loser Architecture

## Overview

Match outcomes distinguish the primary winner recorded on `MatchResult` from the complete winner and loser sets recorded through `MatchWinner` and `MatchLoser`.

- `MatchResult::winner()` is a polymorphic relationship to the primary winning wrestler or tag team. It may be absent for decisions without a winner.
- `MatchResult::winners()` and `MatchResult::losers()` contain every winning and losing match entry, including outcomes with multiple winners or losers.
- Each `MatchWinner` and `MatchLoser` belongs to the exact `MatchCompetitor` entry that participated in the match.
- `MatchCompetitor::competitor()` resolves the participating wrestler or tag team.

The competitor record is the authoritative path from a winner or loser record to its roster entity. Winner and loser models do not duplicate the competitor type or identifier through convenience methods or virtual attributes.

## Database Schema

```text
events_matches_results
├── id
├── match_id → events_matches.id
├── match_decision
├── winner_type (nullable primary winner morph type)
└── winner_id (nullable primary winner morph identifier)

events_matches_competitors
├── id
├── match_id → events_matches.id
├── competitor_type
├── competitor_id
└── side_number

events_matches_winners
├── id
├── match_result_id → events_matches_results.id
└── match_competitor_id → events_matches_competitors.id

events_matches_losers
├── id
├── match_result_id → events_matches_results.id
└── match_competitor_id → events_matches_competitors.id
```

Foreign keys ensure that winner and loser rows cannot reference competitors or results that do not exist. The historical match-deletion workflow soft deletes only the match and preserves these related records.

## Relationship Graph

```text
EventMatch
├── competitors ──> MatchCompetitor ──morphTo──> Wrestler|TagTeam
└── result ───────> MatchResult
                    ├── winner ──────morphTo──> Wrestler|TagTeam|null
                    ├── winners ─────hasMany──> MatchWinner ──belongsTo──> MatchCompetitor
                    └── losers ──────hasMany──> MatchLoser ───belongsTo──> MatchCompetitor
```

The result-level `winner` relationship answers which roster entity is considered the primary winner. The winner and loser collections answer which match competitor records belong to each outcome set.

## Canonical Access

Use relationships directly:

```php
$result = $eventMatch->result;
$primaryWinner = $result?->winner;

$winnerRecord = $result?->winners->first();
$winningEntry = $winnerRecord?->competitor;
$winningRosterEntity = $winningEntry?->competitor;

$loserRecord = $result?->losers->first();
$losingEntry = $loserRecord?->competitor;
$losingRosterEntity = $losingEntry?->competitor;
```

Do not read `winner_type` or `winner_id` from a `MatchWinner`, or `loser_type` or `loser_id` from a `MatchLoser`. Those values are not columns on the outcome-set tables. When the type or identifier is needed, read it from the related `MatchCompetitor`:

```php
$winnerRecord->competitor->competitor_type;
$winnerRecord->competitor->competitor_id;
```

The similarly named `winner_type` and `winner_id` columns on `MatchResult` belong only to its primary-winner relationship.

## Eager Loading

Load both relationship levels when rendering or processing roster entities for multiple results:

```php
$matches = EventMatch::query()
    ->with([
        'result.winner',
        'result.winners.competitor.competitor',
        'result.losers.competitor.competitor',
    ])
    ->get();
```

This avoids querying each competitor record and polymorphic roster entity separately.

## Querying Outcomes

Filter winner or loser rows through their competitor relationship:

```php
$wrestlerWins = MatchWinner::query()
    ->whereHas('competitor', function (Builder $query) use ($wrestler): void {
        $query
            ->where('competitor_type', Wrestler::class)
            ->where('competitor_id', $wrestler->id);
    })
    ->get();
```

Reusable reporting queries belong in a typed query or builder boundary rather than as convenience methods on `MatchWinner`, `MatchLoser`, or `MatchCompetitor`.

## Factory Expectations

Match factories create the result first and then associate outcome rows with existing match competitor records:

```php
$matchResult = MatchResult::factory()->create([
    'match_id' => $eventMatch->id,
    'winner_type' => $primaryWinner?->competitor_type,
    'winner_id' => $primaryWinner?->competitor_id,
]);

foreach ($winners as $winner) {
    MatchWinner::factory()->create([
        'match_result_id' => $matchResult->id,
        'match_competitor_id' => $winner->id,
    ]);
}

foreach ($losers as $loser) {
    MatchLoser::factory()->create([
        'match_result_id' => $matchResult->id,
        'match_competitor_id' => $loser->id,
    ]);
}
```

Tests should assert the foreign-key relationship instead of relying on compatibility attributes:

```php
$winner = $match->result->winners->firstOrFail();

expect($winner->competitor->competitor_id)
    ->toBe($expectedCompetitor->competitor_id);
```
