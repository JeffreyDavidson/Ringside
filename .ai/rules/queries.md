---
paths:
  - 'app/Queries/**'
---

# Queries

## One class, one database query
A Query runs exactly one database query and returns what it found, or the prepared builder for the caller to finish (for example a Livewire page that calls `paginate()`). It is named `{Purpose}Query` in a domain folder. The caller decides what to do with the result: paginate it, count it, reshape it or combine it with other reads. Eager loads (`with`, `withCount` and similar subqueries) belong to that one query; a second `get()`, `count()`, `find()`, `load()` or lazy relation access on a different table does not.

## Several reads for a page are a ViewModel
A class that gathers several reads for one page or payload is that page's ViewModel (`app/ViewModels`, see view-models.md), or the ViewModel calls one single-query Query per read. Do not grow a Query into a bag of reads, a pipeline of dependent lookups or a payload builder. Reusable, chainable filters on one model live on its Builder (builders.md); a Query composes them into its one query.

## One public get()
Inject collaborators through a promoted `private readonly` constructor and expose one public `get(...)` that returns the results (a collection, an array shape declared with `@phpstan-type`, a Data object or a model) or a typed builder the caller finishes. Return null (or an empty result) when nothing can be read; the caller decides what that means.

## Queries only read
A Query takes no locks, writes nothing, validates nothing, throws no business exceptions, builds no URLs and never calls `abort()`, `route()` or `redirect()`. It does not call `findOrFail()` or `firstOrFail()` for a request, because a 404 is the controller's decision. Locks and guards that throw live in Lifecycle (`app/Lifecycle`), validation in the Form Request or Livewire form that feeds the Query, 404s in the controller, and URLs in the controller, Livewire component or ViewModel. Pass a Query only data that has already been validated.

## No view layer, no formatting
A Query never depends on a ViewModel, Presenter or ViewComposer, and it does not format labels or dates, hash values or compare records already in memory. ViewModels, controllers and Livewire components call Queries, never the other way round. Read-only calculations belong in a Service (`app/Services`), the rule in Lifecycle, and display formatting in the page's ViewModel.

## Used by Actions, Lifecycle and Services
Actions, Lifecycle classes and Services may call a Query. When they need several reads combined, split them into single-query Queries plus a Service that combines the results; they never go through a ViewModel (view-models.md).

## Scope and keys
Bypass `PromotionContextScope` only for public or owner reads and always pair it with an explicit `promotion_id` constraint. Obtain polymorphic aliases through `getMorphClass()` and build `type:id` keys with `RosterDateAvailability::key()`.

## Architecture check
`QueryArchitectureTest` fails when a Query depends on the Validator, `ValidationException`, a ViewModel, a business exception, a Lifecycle lock or the `abort()` and `route()` helpers, and requires the `Query` suffix. "One query" is reviewed by hand and by the Query's own test, which asserts the number of statements where it matters.
