# Livewire Testing Guide

## Overview

Livewire components are tested with Pest and the `livewire()` helper from `pestphp/pest-plugin-livewire`.
The general rules (Pest only, Arrange/Act/Assert, 100% coverage, no reflection tests) are in
[Testing Guidelines](../../development/testing-guidelines.md) and `.ai/rules/tests.md`. This guide covers the
Livewire-specific parts.

## Where tests live

Component tests are Integration tests. They mirror `app/Livewire` one to one:

```text
tests/Integration/Livewire/
├── Base/                      (BaseForm, BaseModal, BaseFormModal, base tables)
├── Components/                (GeneralInfo and shared table columns/filters)
├── Concerns/                  (ExecutesRosterActions, ExecutesBusinessActions, ...)
├── Support/
├── Table/
└── {Domain}/
    ├── Components/ActionsTest.php
    ├── Forms/CreateEditFormTest.php
    ├── Modals/FormModalTest.php
    └── Tables/MainTest.php
```

`tests/Unit/Livewire` is reserved for framework-free pieces such as the Matches enums. Browser journeys live in
`tests/Browser` (see [Browser Testing](testing-browser.md)).

## Setup

`tests/Pest.php` binds `RefreshDatabase` to the Integration suite and freezes the clock, so tests do not call
`freezeTime()` themselves. Use `travel()` to move time. Import the Pest helpers rather than using `$this`:

```php
use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});
```

`administrator()` and `basicUser()` are global helpers in `tests/Pest.php`. Other shared helpers live in
`tests/Helpers` (for example `forceFakerBoolean()` in `FakerHelpers.php` and the `create*()` builders in
`TestHelpers.php`). Use model factories and their states (`Wrestler::factory()->employed()`) for data.

Test the component through its public interface:

```php
it('filters wrestlers by name', function (): void {
    // Arrange
    Wrestler::factory()->create(['name' => 'John Cena']);
    Wrestler::factory()->create(['name' => 'The Rock']);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'John');

    // Assert
    $component
        ->assertSee('John Cena')
        ->assertDontSee('The Rock');
});
```

Mount parameters are passed as the second argument: `livewire(Actions::class, ['wrestler' => $wrestler])`.
Use fixed, distinctive values for search terms and names; never let random Faker output choose a branch.

## Component guides

- [Form Testing](testing-forms.md): `BaseForm` subclasses, typed data, hydration, validation.
- [Modal Testing](testing-modals.md): `BaseFormModal` lifecycle, authorization, titles, events.
- [Table Testing](testing-tables.md): `BaseTable` subclasses, search, filters, row actions.
- [Actions Testing](testing-actions.md): lifecycle `Components/Actions` components.
- [Browser Testing](testing-browser.md): Pest Browser journeys.
- [Best Practices](testing-best-practices.md): conventions that apply to every Livewire test.

## Running tests

```bash
php artisan test --compact tests/Integration/Livewire
php artisan test --compact tests/Integration/Livewire/Wrestlers
php artisan test --compact --filter="wrestlers table"
```

Composer scripts are the source of truth for full runs: `composer test:application` (parallel, excludes
Browser), `composer test:browser`, `composer test:coverage` (non-parallel, enforces 100% coverage of `app/`),
`composer test:types`, `composer test:rector`, and `composer test:lint`. `composer test:push` runs the set
required before pushing.

## Related Documentation

- [Component Architecture](../../architecture/livewire/component-architecture.md)
- [Form Patterns](../../architecture/livewire/form-patterns.md)
- [Modal Patterns](../../architecture/livewire/modal-patterns.md)
- [Testing Guidelines](../../development/testing-guidelines.md)
