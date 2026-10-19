---
paths:
  - 'app/Providers/**'
---

# Providers

## Bootstrapping only
`AppServiceProvider` wires the framework to the application: scoped services (`PromotionContextService`), the case-insensitive user provider, `Gate::before`, the lazy-loading guard, password defaults, forced HTTPS, view composers, Livewire persistent middleware and the enforced morph map. It contains no business rules, queries or workflow; those belong in Actions, Lifecycle classes and Services.

## Keep the morph map and its test in step
The morph map has ten aliases: wrestler, manager, match, title, tag_team, referee, stable, event, venue and user. When a polymorphic model is added, change the map and `tests/Feature/Architecture/EloquentMorphMapTest.php` in the same commit.

## Legacy class aliases stay
The `class_alias` calls for the former top-level roster model names exist because merged migrations import them and are never edited. Do not remove them until those migrations are gone.

## Register once, in the right place
Register observers with `#[ObservedBy]`, global scopes in the model or `BelongsToPromotion`, and middleware in `bootstrap/app.php`; do not register them in a provider.
