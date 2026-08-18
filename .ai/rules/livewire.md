---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Explicit Livewire authorization
Authorize every protected Livewire operation with Gate::authorize() immediately before performing it.

## Direct Eloquent access
Query Eloquent models directly in Livewire query components. Move reusable query behavior into typed custom Eloquent Builders; do not create repositories that merely wrap Eloquent.

## Resolve actions at Livewire boundaries
Resolve Action classes from the container at Livewire execution boundaries and call handle().

## Blade and Livewire frontend
Build server-driven interactive UI with class-based Livewire components and Blade views; do not introduce an Inertia or SPA page layer without an explicit architectural change.

## Class-based Livewire components
Implement Livewire behavior in namespaced PHP classes using shared base classes where applicable. Do not introduce Volt or single-file components.

## Translate only domain failures
Catch and translate BaseBusinessException at Livewire user-interaction boundaries. Do not catch generic Exception or Throwable to produce business flash messages; let programmer, infrastructure, and framework failures propagate through Laravel's normal exception reporting.

## Populate development data through typed forms
Implement dummy-data population by assigning directly to the concrete Livewire form's typed properties. Do not return generic field-name arrays or dynamically assign arbitrary properties; dedicated collaborators such as match form population must accept the concrete form type.
