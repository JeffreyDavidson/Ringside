# Tournament Management

Ringside supports promotion-owned single elimination tournaments for individual wrestlers or established tag teams. A tournament uses one entrant kind, 4, 8 or 16 bracket slots, and between two and the selected capacity of unique entrants. Eight is the default capacity. Rounds can span different events; the optional **Allow multiple rounds on one card** setting enables the bounded same-event exception described below.

## Storage and ownership

Four additive tables hold tournament planning and links to ordinary matches:

- `tournaments`: promotion, name/description, entrant kind, capacity, repeat-appearance setting, Draft/Active/Completed/Cancelled status, version and optional client create request UUID.
- `tournament_entries`: promotion/tournament, manual seed, wrestler or tag-team morph identity, and withdrawal state. Unique seeds and competitor identities are enforced per tournament. Competitor relationships include deleted records so history remains readable.
- `tournament_nodes`: promotion/tournament, round/position, first-round seed inputs, optional planned event and an explicitly audited administrative walkover. Unique round/position is enforced per tournament.
- `tournament_match_links`: promotion/tournament/node, ordinary match, attempt number and nullable active-node key. A match has one link; a node has at most one active link. Superseded attempts remain historical links.

All four models use `BelongsToPromotion` and protected mass assignment. HTTP routes and every Livewire operation authorize through the existing promotion gate: Owners/Managers operate, Members read, and an enforced promotion context remains binding for administrators. The ordinary event and roster ownership checks still apply to every booking.

## Seeds, byes and planning

`SaveTournamentAction` validates eligible unique entrants and creates bracket nodes without creating any real matches. `BracketLayout` produces standard seeded positions; eight slots pair 1–8, 4–5, 2–7 and 3–6. `TournamentBracketService` resolves nodes in round/position order from the recorded predecessor winners. Missing first-round seeds become byes, and empty branches collapse deterministically. Byes create neither results nor championship history.

Draft setup may change capacity, kind, seed order and the repeat setting. The first confirmed real booking makes the tournament Active, freezes those settings and audits the accepted configuration. Name and description remain editable. A planned event on an unresolved node is tentative: it reserves no appearance and creates no event-card match.

## Actual booking and outcomes

`BookTournamentMatchAction` builds the existing `EventMatchData` from resolved entrants and calls `AddMatchForEventAction`. The latter locks and rechecks tournament version, entrants, format, current event state, chronology, ordinary competitor eligibility/conflicts, referees and optional final title stakes. It creates a normal `EventMatch`, then the link, and invokes the existing assignment actions in the same transaction. Identical pending booking retries return the existing match; conflicting stale reviews require a refresh.

Only Singles or standard TagTeam matches are accepted for the corresponding kind. Titles are optional on the final only and retain all ordinary type, promotion, champion and once-per-card rules. Unknown qualifiers and byes cannot be booked. Advancement never automatically creates a later match.

The ordinary result modal and `RecordResultAction` remain the sole competitive outcome writers. Linked result forms carry an expected tournament version. Winner-changing edits to recorded outcomes require the current version; independent first results for untouched sibling bouts may both commit despite a sibling version increment. Identical result retries do not advance the tournament version again.

`TournamentOutcomeManager` guards edits and synchronizes completion within the same outer transaction as match results and championship reconciliation. The bracket derives its competitive winner from `match_finish` and `winning_side_id`; it stores no duplicate competitive result. Every decisive finish advances its winning side. Time-limit draw and no-decision leave the bout unresolved and require an explicitly booked rematch. The prior attempt and result remain intact. DQ/countout advance the tournament winner while existing championship rules retain the title. A decisive final completes the tournament; a permitted correction can reopen it.

## Same-card exception and chronology

Repeat appearances are disabled by default. When enabled, `TournamentMatchRequirements` supplies only the exact prior linked appearances on the entrant's ancestor path, or an earlier recorded attempt of the same node, for this same tournament and event. `MatchCompetitorAssignmentEligibility` excludes those match ids only from the matching identity booking query. Unrelated matches, another tournament, parallel branches, different events at the same instant, and wrestler/team-member overlaps remain conflicts. Referees continue to work multiple bouts on one card. Standalone assignment actions check linked entrants and final-only stakes too.

Across distinct events, a descendant or rematch event must be strictly later than its qualifying or previous attempt event. Same-event progression depends on recorded qualification and the enabled repeat setting. First-round matches may be booked on unscheduled events, but the ordinary result guard still refuses future/undated events. Event Update/Restore actions recheck linked chronology. Rescheduling, deleting or restoring a tentative planned event increments the affected tournament versions so an old review cannot silently use changed event state.

## Corrections and administration

Winner-preserving corrections need no dependency reset. Winner-changing corrections refuse when a descendant has an active booked match or administrative advancement. `ResetTournamentBranchAction` requires a reason and explicit UI review, locks the affected branch, and soft-deletes only unplayed bookings. It preserves the old matches/links and audits affected ids. A reset with no booked or administrative dependency is a no-op and cannot activate a draft. Recorded or live-started descendants cannot be reset. Ordinary match deletion refuses any tournament-linked match; ordinary event deletion refuses active tournament links.

Withdrawals mark an entry without erasing its played results. An unplayed linked bout involving that entry is removed through the reviewed reset flow. Replacements are limited to entrants with no recorded bout or administrative advancement, clear their unplayed branch and recheck the replacement under lock, then increment the tournament version even when no booking needed resetting. A walkover requires exactly one withdrawn entrant and an eligible opposing entrant, known inputs and no active match; it is an audited administrative advancement, never a title transfer. Cancellation preserves played history and removes reviewed pending bookings; a completed tournament cannot be cancelled.

The Livewire screen retains entered review values after a business refusal. Reset, removal, withdrawal, replacement, walkover and cancellation require an accepted preview and reason. Audit records use the `tournaments` activity log with promotion/tournament ids, actor supplied by Activitylog, relevant records and the reason.

## Transactions and locking

`TournamentLocks` snapshots all linked/planned event ids and date peers, locks that complete event union in ascending id order, then tournament rows in ascending id order. It rechecks event membership and dates after obtaining locks and refuses a changed snapshot rather than acquiring late event locks. Existing name/date-slot locks still precede this union. Match, assignment, competitor, live-run and title locks follow their established order. Outer mutation transactions retry boundedly where their database-only work permits it.

SQLite tests prove rollback and invariant behavior; native PostgreSQL/MySQL process tests are required to establish row-lock behavior. The bounded tournament worker covers duplicate booking, opposing results, sibling qualification, correction versus dependent booking, ordinary assignment versus exemption, event reschedule and employment release. A release after a committed booking remains allowed under the existing roster lifecycle rules; it makes that competitor unavailable for a subsequent booking without erasing an already booked match.

## Event packs, live show and interface

Actual tournament matches enter the ordinary event card, pack and live-show source query. Adding a qualified later-round match changes the card fingerprint and requires explicit pack review followed by live-show review. Existing completed item timings and arrivals are retained; completing a live item never records a competitive result. Projected entrants remain planning data, and labelled show segments remain segments.

Tournament routes use the existing operations shell and visual identity. Phones default to readable round rows; the optional conventional bracket scrolls within a bounded region. Standard result modals refresh the bracket through `refreshDatatable`. Manual seeding has accessible move controls, review panels name affected records, dates use promotion-local time, and winner/draw/bye/walkover states use text alongside color.

Round robin, points, double elimination, custom bracket graphs, mixed kinds, automatic booking, retroactive linking, public brackets, exports and communications are deferred. No availability, settlement, appearance-fee or storyline semantics are implied by tournament qualification.
