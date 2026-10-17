---
paths:
  - 'app/ViewModels/**'
---

# ViewModels

## Domain folders
ViewModels live in `app/ViewModels/{Domain}/` (`Events/`, `Storylines/`, `Dashboard/`), never directly in `app/ViewModels`, like Queries and Actions.

## A ViewModel only builds a page's data
A ViewModel is named after the page or payload it builds (`EventBudgetViewModel`) and exists only when a page combines several sources or needs reusable display logic. It reads through Queries (each runs one database query, queries.md), Builders and read-only Services and returns the page's data. When a class gathers several reads for one page, it is that page's ViewModel, not a Query. It writes nothing, takes no locks and runs no Action.

## Called only from the view layer
Only controllers and Livewire components call a ViewModel (controllers method-inject it, Livewire components resolve it in `render()` or an action). Actions, Queries, Services, Lifecycle classes and other ViewModels never depend on one, and Blade never resolves one. When a write path needs the same data, it uses the underlying Query (`EventPackSourceQuery`, `EventPackCardQuery`), which the ViewModel also uses. A page builds its data once per render: share a Query, do not nest ViewModels.

## A typed, ready-to-render array
Expose one `data()` method (or one method per distinct payload) that returns an array whose shape is declared with `@phpstan-type`. Values arrive already formatted: dates, labels, statuses and money are strings the view prints. The view receives the array, never the ViewModel object, and does not call methods on it. Anything a Blade loop needs per row (a status, a comparison, a fee) is computed by the ViewModel into the array, keyed by the row's id or key.

## Money uses UsdAmount
Format money with `App\ValueObjects\UsdAmount`; do not re-implement cents arithmetic. A page that needs a different layout (a currency prefix, thousands grouping) builds it from `UsdAmount::decimal()` so the cents and the sign stay correct for negative amounts.

## No throwing permission checks
A ViewModel never calls `Gate::authorize()` or `abort()`. The route (`->can()`) or the Livewire component authorizes before calling it, including in `render()` when the component can re-render after the viewer's access changed. `Gate::allows()` and `Gate::denies()` are allowed only to leave private fields out of the payload (production notes, crew entries); they never decide whether the page may be seen.

## Architecture check
`ViewModelArchitectureTest` fails when an Action or Lifecycle class depends on a ViewModel, when a ViewModel depends on an Action or on another ViewModel, and (with `QueryArchitectureTest` and `ServiceArchitectureTest`) when a Query or Service does. Test a ViewModel at the path that mirrors it under `tests/Integration/ViewModels/`, including any domain folder (`app/ViewModels/Tournaments/TournamentShowViewModel.php` is tested at `tests/Integration/ViewModels/Tournaments/TournamentShowViewModelTest.php`) with real records, asserting the returned array.
