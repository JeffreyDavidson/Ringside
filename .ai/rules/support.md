---
paths:
  - 'app/Support/**'
---

# Support

## Technical building blocks only
app/Support holds technical building blocks that know nothing about this app, the way Illuminate\Support does for the framework. A class belongs here only if all five tests hold; otherwise it is a Presenter, ViewModel, Query, Action, Service, enum, config or domain class.

## The five Support tests
1. It imports nothing from App\ outside App\Support, and nothing from Filament or Livewire. SupportArchitectureTest enforces this with a Pest architecture expectation.
2. It names no route, model, content slug or app-specific string (including app translation keys).
3. Its main job is not IO or changing state, and it never calls abort() or redirect() or reads the Request.
4. A product owner would not describe what it does as a business rule.
5. It can be named after a technical concern (Feeds, Monitoring, Seo, Time).

## Group subfolders by technical concern
Group subfolders by technical concern, never by domain area. A Content or Newsletter folder in Support means misplaced code. Custom authentication classes live in app/Auth and enum concerns in app/Enums/Concerns.

## Search
LikeTerm (app/Support/Search) cleans a typed term for a "contains" LIKE query: it drops the LIKE wildcards (%, _) and the backslash instead of escaping them, trims, and can cut the term to a maximum length. Build the term once with `LikeTerm::from($term, $maxLength)`, skip the filter when `isEmpty()`, and pass `contains()` to `whereLike()`. Do not repeat the `str_replace` and `mb_trim` expression inline. Terms that are only trimmed or cut, and never stripped (sponsor names, title history), are not LikeTerm searches.
