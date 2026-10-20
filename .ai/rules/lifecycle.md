---
paths:
  - 'app/Lifecycle/**'
---

# Lifecycle

## Reuse lifecycle projection reads
Use LifecycleStateReader::readProjectedBoolean for boolean query projections before relationship fallbacks. Status readers should share this helper so projected list queries remain query-free without duplicating attribute inspection logic.

## Hold the guards and locks that queries must not
Row locks, `ensure…` guards and the business exceptions they throw live in app/Lifecycle, not in app/Queries (see queries.md). A Query returns what it read, or null when there is nothing to return; the Lifecycle class decides what that means and throws. EventRosterSchedulingEligibility locks the roster on an event's card and checks the new date; LiveShowSourceRequirements refuses a live show source that needs preparing.

## Lifecycle holds the state rules
app/Lifecycle holds the rules about a record's state: `ensureCan...` and `can...` eligibility guards, requirement checks that refuse an operation the current state does not allow (`...Requirements`), lifecycle period writers, and the row locks that keep concurrent operations in one order (`...Lock`). Name a class by its role: `...Eligibility`, `...Requirements`, `...PeriodManager`, `...Lock`, `...StateMachine`. A guard may take the locks it needs to be reliable, inside the caller's transaction.

## A StateMachine lists a record's allowed status moves
A stored status that changes in more than one place gets a `...StateMachine` class in app/Lifecycle (`final readonly`, for example `TalentBookingStateMachine`). It is the only place that says which moves are allowed:
- **Allowed moves table:** one constant maps each status to the statuses it may move to. Anything not listed is refused, and `canMove($from, $to)` reads only that table. A record that does not exist yet starts from the status a new record has. The table is required only when some move can be refused; a state machine whose moves are all allowed (sponsor promises) or already guarded by an earlier lock (live show) keeps only its reachable rules, with no table and no `canMove()`.
- **Reason rules:** `requiresReason($from, $to)` says which moves must be explained, as a function of the two statuses alone.
- **Data guards:** a move that also depends on data (a dated event, a recorded agreement, no open steps) is an `ensure...` method that takes the record and the data it needs as arguments (`ensureCanConfirm` takes the booking plus whether the event is scheduled and whether the agreement is recorded), so the state machine reads no query and takes no lock.
- **`ensureCanMove($record, $to, $reason)`** refuses with the domain's business exception, in the order the Action always checked: the table first, then the reason. Any further `ensure...` guard is called by the Action in its own place in the sequence.
- **Refuses, never writes:** a state machine never locks, saves, records a revision or reads the database. The Action locks the record, calls it, then writes and audits. Keep the Action's lock order, version check and revision recording where they are.
- **Test:** a dataset test lists every from → to pair, including a status moving to itself, with the expected `canMove()` and `requiresReason()` for each, plus the exact exception and message for each refusal and the order in which two refusals apply. Also cover the Action's wiring with a test per refused move (removing the Action's call to the state machine must fail a test). Compute nothing in the dataset from the table under test.

## Lifecycle may read through Services, never the reverse
A Lifecycle class may inject a read-only Service or Query. A Service never depends on Lifecycle (see services.md), so a guard or lock that a Service would need to call belongs in Lifecycle, with the read it relies on left in the Service.

## Lock an event's card through EventCardLock
Actions that read or write an event's card under its fingerprint (event pack, production, sponsor instructions, live show, storyline sources) take EventCardLock inside their transaction instead of locking those rows by hand. It locks the event, all its matches including deleted ones in ascending id order, the pack and the live run, in that order; lock any other row of the event (a sponsor commitment) after it.

## Keep live item rules in their transition classes
Each live-show item operation has one class in app/Lifecycle/Events/LiveItemTransitions that checks the item and returns it updated, and LiveItemTransitionResolver picks it. Transitions never write: TransitionLiveItemAction keeps the lock, the reason check, the run status and the persist. Compare and store item states through LiveItemStatus values, never string literals.

## One class per case, picked by a resolver
When one operation branches on an enum (a live item operation, a public section) and each case has its own checks, give each case its own class in a folder named for the operation and put them behind one small interface that states what the class checks, what it returns or fills, and that it never writes. A `...Resolver` takes the case classes by constructor injection and returns the right one with a `match` on the enum, so adding a case is a new class plus one match arm. The Action keeps everything the cases share: the lock, the persist and the audit. Put checks that every case repeats in one shared class both cases call (`PublicationDraftRequirements::ensureDraftReleasable()`), not in an abstract parent. Examples: LiveItemTransitions (`StartLiveItem`, `FinishLiveItem` and the others, picked by `LiveItemTransitionResolver`) and PublicationReleases (`ReleaseCard` and `ReleaseResults`, picked by `PublicationReleaseResolver`, which fill the publication's fields while `ReleaseEventPublicationAction` locks, saves and logs). Test each case class with its refusals and its allowed result, and the resolver with one case per enum value.
