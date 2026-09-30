# Actions Component Testing

## Overview

Each roster and title detail page renders a `{Domain}\Components\Actions` Livewire component (wrestlers,
managers, referees, tag teams, titles, and stables). It exposes one method per lifecycle transition, resolves the
typed Action from the container, and runs it through `ExecutesRosterActions::executeAuthorizedRosterAction()`
(titles and stables use `ExecutesBusinessActions`). `canPerform()` decides which buttons render. See
[Livewire Standards](../../architecture/livewire-standards.md#lifecycle-actions-components).

Tests live in `tests/Integration/Livewire/{Domain}/Components/ActionsTest.php`. Action business rules are tested
in the Action's own tests; here, test delegation, feedback, authorization, and button visibility.

## Delegation and feedback

Replace the Action with a `JMac\Testing\Double`, bind it in the container, call the component method, and assert
the dispatched events. Use a dataset for the transition methods:

```php
$action->expects('handle')->with(
    Argument::satisfies(fn (mixed $actual): bool => $actual instanceof Wrestler && $actual->is($wrestler)),
);
app()->instance($actionClass, $action);

actingAs(administrator());
$component = livewire(Actions::class, ['wrestler' => $wrestler]);

$component->call($method);

$component
    ->assertDispatched('wrestler-updated')
    ->assertDispatched('flash-message', type: 'status', message: $message);
$action->verify();
```

Source: `tests/Integration/Livewire/Wrestlers/Components/ActionsTest.php`. Success dispatches `{entity}-updated` (the
event `App\Livewire\Components\GeneralInfo` listens for) and a `flash-message`.

## Authorization

Every method calls `Gate::authorize()` first. A user without the ability gets a forbidden response and no
success feedback:

```php
actingAs(basicUser());
$component = livewire(Actions::class, ['wrestler' => $wrestler]);

$component->call($method);

$component
    ->assertForbidden()
    ->assertNotDispatched('wrestler-updated')
    ->assertNotDispatched('flash-message');
```

## Button visibility

`canPerform()` combines the Gate ability with the domain eligibility check. Assert the rendered
`wire:click` attributes for each state, using the factory state as the dataset key:

```php
$wrestler = Wrestler::factory()->{$state}()->create();

$component = livewire(Actions::class, ['wrestler' => $wrestler]);

$component->assertSeeHtml('wire:click="employ"');
$component->assertDontSeeHtml('wire:click="release"');
```

Also assert that buttons for an unauthorized user are hidden (`canPerform()` returns `false`), and that the
buttons change after a successful transition. Titles pass a `TitleLifecycleTransition` and stables a
`StableLifecycleAction` to `canPerform()`; their tests follow the same shape.

## Domain failures

`BaseBusinessException` thrown by an Action is caught at this boundary and turned into a failure flash message
(`dispatchActionFailure()`), without `{entity}-updated`. Make the Double throw the exception and assert a
`flash-message` event with `type: 'error'` and that `{entity}-updated` was not dispatched (see the failure case in
`tests/Integration/Livewire/Stables/Components/ActionsTest.php`). Only domain exceptions are caught; other
exceptions propagate.

## Related Documentation

- [Testing Guide](testing-guide.md)
- [Table Testing](testing-tables.md)
- [Action Testing](../../testing/action-testing.md)
