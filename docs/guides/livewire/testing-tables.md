# Table Component Testing

## Overview

Index tables extend `App\Livewire\Base\Tables\BaseTable`, which extends `App\Livewire\Table\DataTableComponent`.
A table defines `builder()`, `columns()` (built from `App\Livewire\Table\Column` and `Table\Columns\*`), optional
`filters()` (`App\Livewire\Table\Filters\SelectFilter`, `DateRangeFilter`), and a `configure()` method that
authorizes access. Relationship history tables on detail pages extend a `Base\Tables\BasePrevious*Table`.

Table tests live in `tests/Integration/Livewire/{Domain}/Tables/`, one file per table (`MainTest.php`,
`PreviousManagersTest.php`, ...).

## Rendering

Assert what the user sees, not the internal column array:

```php
it('renders the configured table controls and wrestler attributes', function (): void {
    // Arrange
    Wrestler::factory()->create(['name' => 'Big Wrestler', 'hometown' => 'Test City, TX']);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Add Wrestler')
        ->assertSeeHtml('placeholder="Search wrestlers"')
        ->assertSee('Big Wrestler')
        ->assertSee('Test City, TX');
});
```

Source: `tests/Integration/Livewire/Wrestlers/Tables/MainTest.php`.

## Search

The search term is the public `search` property. Use fixed names so the result does not depend on Faker:

```php
$component->set('search', 'John');

$component
    ->assertSee('John Cena')
    ->assertDontSee('The Rock');
```

## Filters

Filter state lives in `filterValues`, keyed by `Filter::getKey()`, the snake-cased filter name (for example `status`). Drive it from a
dataset of the enum cases:

```php
$component->set('filterValues.status', $status->value);

$component
    ->assertSee($visibleWrestler->name)
    ->assertDontSee($hiddenWrestler->name);
```

Filter state is client-controlled, so cover malformed values (for example date ranges) and assert they are
ignored rather than raising an error.

## Row actions

Row-level delete goes through the table's `delete()` method, which authorizes and runs the typed Action. The
shared deletion behavior for every table is asserted once in `tests/Integration/Livewire/DeletionActionsTest.php`:

```php
livewire($component)
    ->call('delete', $owner)
    ->assertHasNoErrors()
    ->assertDispatched('flash-message', type: 'status', message: __($translationKey));
```

Lifecycle transitions (employ, retire, ...) are not table actions; they live in the detail page
`Components/Actions` component (see [Actions Testing](testing-actions.md)).

## Authorization

`configure()` authorizes on mount, so an unauthorized user gets a forbidden response:

```php
it('forbids users without wrestler access', function (string $actor): void {
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    livewire(Main::class)->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
```

## History tables and context ids

History tables take their parent id from a `#[Locked]` public property (for example `wrestlerId`) and throw a
`LogicException` when it is missing. Test the query through `builder()`:

```php
$table = new PreviousManagers;
$table->wrestlerId = $wrestler->id;

$assignments = $table->builder()->get();

expect($assignments->pluck('manager_id')->all())->toBe([$recentManager->id, $olderManager->id]);
```

Source: `tests/Integration/Livewire/Wrestlers/Tables/PreviousManagersTest.php`. Dates in these tests use
`Date::now()`; the clock is frozen, so expected values are stable.

## Refreshing

Simulate a data change with `$component->call('$refresh')` and assert the new output.

## Related Documentation

- [Testing Guide](testing-guide.md)
- [Actions Testing](testing-actions.md)
- [Livewire Standards](../../architecture/livewire-standards.md)
