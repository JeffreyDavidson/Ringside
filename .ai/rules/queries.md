---
paths:
  - 'app/Queries/**'
---

# Queries

## A Query is a complete read
A Query is one class per read, named `{Purpose}Query` in a domain folder, that gathers everything one page or payload needs. Builders are the reusable, chainable filters on one model; a Query composes them. Do not turn a Query into a bag of unrelated lookups: one purpose, one class.

## One public get()
Inject collaborators through a promoted `private readonly` constructor and expose one public `get(...)` that returns a typed builder, a Data object or an array shape declared with `@phpstan-type`. Return null (or an empty result) when nothing can be read; the caller decides what that means. A class with shared private helpers may be abstract, with a final subclass per read.

## Queries only read
A Query takes no locks, writes nothing, validates nothing, throws no business exceptions, builds no URLs and never calls `abort()`, `route()` or `redirect()`. Locks and guards that throw live in Lifecycle (`app/Lifecycle`), validation in the Form Request or Livewire form that feeds the Query, 404s in the controller, and URLs in the controller, Livewire component or ViewModel. Pass a Query only data that has already been validated.

## No view layer
A Query never depends on a ViewModel, Presenter or ViewComposer. ViewModels, controllers and Livewire components call Queries, never the other way round; two reads that share data share a Query.

## Formatting and hashing are not Queries
A class that mostly formats, hashes or compares values already in memory is not a Query. Put read-only calculations in a Service (`app/Services`), the rule in Lifecycle, or display formatting in the page's ViewModel. A Query is allowed to read a few attributes of a record it was given, but its job is the database read.

## Scope and keys
Bypass `PromotionContextScope` only for public or owner reads and always pair it with an explicit `promotion_id` constraint. Obtain polymorphic aliases through `getMorphClass()` and build `type:id` keys with `RosterDateAvailability::key()`.

## Architecture check
`QueryArchitectureTest` fails when a Query depends on the Validator, `ValidationException`, a ViewModel, a business exception, a Lifecycle lock or the `abort()` and `route()` helpers, and requires the `Query` suffix.
