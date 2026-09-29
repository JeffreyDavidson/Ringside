---
paths:
  - 'app/**'
---

# App

## Explicit query eager loading
Eager-load relationships explicitly on queries that need them with with(); do not add model-level $with defaults.

## Synchronous domain orchestration
Compose domain workflows directly through Actions and Services. Do not add an application event/listener layer unless a workflow specifically requires event fan-out.

## Layer-first application namespaces
Organize application code by technical responsibility at the top level, then by wrestling entity within each layer. Do not introduce parallel domain or module roots.

## Use date helpers for the current date
Use now() and today() for the current timestamp or date. Use Carbon constructors for parsing or constructing explicit date values.

## Classify exception boundaries by failure source
Use typed BaseBusinessException subclasses for domain-rule rejections. Use LogicException for impossible programmer or configuration states, including missing convention-derived model classes and invalid trait hosts. Reserve InvalidArgumentException for invalid values supplied by a caller; do not directly construct generic Exception.

## Use Laravel before custom infrastructure
Prefer Laravel's native framework abstractions and APIs before introducing custom helpers, normalization, infrastructure, or replacement patterns. Preserve Eloquent and framework types through typed models, relationships, builders, casts, validation, and collections; add custom code only when Laravel does not provide the required behavior.

## Persist request-scoped context middleware for Livewire
Middleware that establishes request-scoped context, such as EstablishPromotionContext, must also be registered with Livewire::addPersistentMiddleware(). Livewire update requests do not run the page route's middleware, so without it the context is lost after the first interaction.

## No orphaned docblocks
A docblock must sit directly above the declaration it documents, and prose that only restates a typed signature should be omitted. The DocblockArchitectureTest architecture test fails on orphaned docblocks.
