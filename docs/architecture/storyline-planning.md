# Storyline planning

Storylines connect private creative plans and confirmed developments across shows. They do not book talent, record match results, change championships, or publish content.

## Ownership and access

Every story, participation, step, development and revision has a required promotion owner. Storyline uses the existing promotion scope and an explicit policy allowing active Owners and Managers to read and write. Members have no creative read access. PromotionGate falls through to this policy before its ordinary promotion-content role handling. The platform administrator exception and foreign-promotion guard remain unchanged.

Routes, each component render, roster/source searches and every `Manage`/`Create` save authorize `view`, `update` or `create` before an Action runs; the Actions do not check permissions or validate input. The components validate each form field (lengths, dates, reason requirements) before building the Data object. Child operations resolve through the authorized story. Event pages query related story titles only after the creative read check; pack printing, live JSON, event/match previews and public surfaces receive no story fields.

## State and persistence

Storyline owns Active, Paused, Completed and Archived state plus a version. Completion requires all Planned/Postponed steps resolved. Archive freezes editing and retains the prior state for explicit restoration. No storyline purge endpoint exists.

Participants reference existing wrestler/tag_team morph aliases. Joining records a dated period; ending closes it; rejoining creates a new row. A generated nullable open-participant key and unique index enforce one open involvement per identity/story on SQLite, PostgreSQL and MySQL. Creative participation does not require booking eligibility. Ending an involvement retains its label and cannot change its identity or joining date.

Steps own the planned description, sequence and Planned/Postponed/Cancelled/Happened status. Event-linked targets derive from Event.date. Standalone dates are allowed only without an event. Their saved timezone and derived UTC start-of-day target_at allow comparison with UTC event instants; wording-only edits retain that timezone. target_at is a derived sort value, not another editable scheduling authority. The original source context remains in durable revisions when the plan is retargeted. Postponements/cancellations require reasons and do not become Next planned until explicitly resumed.

Development records contain a booker-confirmed actual date, same-day sequence, description, cast and optional step/source links. Dates cannot be in the future. Event-linked accounts require an occurred event and the same local date. The recording timezone is retained through corrections. Recording a linked development and setting its step Happened commit together; a unique nullable step FK prevents a second account for that step. Retraction retains the record, removes it from Last happened and explicitly reopens or cancels its step. Reconfirmation corrects the retained account rather than inserting another one.

Actual cast labels are snapshots. Teams do not automatically claim their current wrestlers performed. Selected participation periods must include the actual date. Later roster renames or team membership changes cannot rewrite a saved cast, including a wording correction with the same identities.

Mass assignment follows the app-wide rule. Only fields that come from the validated step, participant and development forms are `#[Fillable]`, and the Actions write them with `fill()`: step heading, description, sequence and target date (`StorylineStep`); role and joined/left dates (`StorylineParticipant`); heading, description, occurred date and sequence (`StorylineDevelopment`). Ownership and parent keys (`promotion_id`, `storyline_id`, `storyline_step_id`), source links (`event_id`, `match_id`, `segment_id`, resolved and locked by `LockSourceAction`), step status, versions, submission ids, time zones, derived target instants, source context snapshots, the cast and participant label snapshots, and the participant identity are assigned explicitly by the Action. `StorylineRevision` is an audit row written only by `RecordRevisionAction`, so its fillable list is empty and mass assignment throws.

## Source authority

`StorylineSourceService` reads current event, match, saved-segment UUID and live-item evidence through `StorylineSourceEventQuery`, `StorylineSourceMatchQuery` and `StorylineSourcePlacementQuery`; it returns nothing for an event or match the promotion does not have, and the caller decides that is a 404. Match and segment links are exclusive and must belong to the chosen event and promotion; the planning form validates that combination before the service reads it. Removing/recreating a same-labelled segment cannot restore its old UUID. Deleted sources and unplaced segment anchors remain labelled historical evidence. Skipped, finished and missing live states never determine the creative outcome automatically. A changed/contradictory source needs explicit explanation at confirmation.

Opening forms captures a current source fingerprint; mutations compare it under source locks. Event rescheduling changes the derived target but never shifts other steps. Saved event-date context exposes drift for review. Storyline screens link back to the authoritative pack/live workflows and cannot write their content, timing or results.

## Concurrency and history

Source-linked Actions lock the event card through `EventCardLock` (the event, all its matches in ID order, pack and live run) before the story. Other mutations lock the story. Under that lock Actions re-read the expected story version and check state; they do not authorize again, because the calling component already did. Stale/repeated transitions change nothing. New stories serialize on their promotion and use a unique form submission UUID; steps and accounts also have unique submission identities.

RecordRevisionAction writes private before/after data with actor, timestamp and reason in the mutation transaction, then increments the story version. StorylineRevision is separate from general activity-log cleanup, so creative history is durable. Source FKs restrict hard deletion; ordinary source soft deletion retains links. There are no outward events/listeners, emails or scheduled creative transitions.

## Interface and queries

Class-based Livewire/Blade pages use the existing shell, brand tokens and form components. Last happened excludes retracted accounts and orders by actual date, same-day sequence and ID. Next planned orders by current event/standalone target, then sequence/ID, with undated steps last. Paused stories label their next step as paused. The list excludes Archived by default and has search/status/participant filters.

The next-step ID is a correlated subquery, and last-account, participant and source relationships are explicitly eager loaded. Timelines and revisions have separate paginators. Creative roster searches are bounded to 20 live records of the story's promotion, without the match form's bookability filter. Event searches are bounded, retaining an already selected event when it falls outside the search results. Form drafts survive validation/stale failures; a stale write requires explicit reload/review before another attempt.

Tests cover tenant/role boundaries, revocation after opening a form, actual versus planned accounts, history/corrections, cast identity, source drift/deletion, repeated saves, database uniqueness, lock order, real-process PostgreSQL/MySQL races and a phone/desktop browser journey.
