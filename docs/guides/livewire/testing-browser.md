# Browser Testing

Ringside browser tests use Pest Browser with Chromium. They exercise the
application through a real browser and belong in `tests/Browser`; they do not
use Laravel Dusk or a separate `DuskTestCase`.

## When to use browser tests

Use a browser test only when the behavior depends on browser execution, such
as JavaScript, Livewire interaction, navigation, keyboard behavior, responsive
layouts, or client-side validation. Keep server-side validation, persistence,
and action behavior in Feature or Integration tests so the browser suite stays
small and fast.

## Test setup

`tests/Pest.php` binds `RefreshDatabase` to the Browser suite. Do not add
`DatabaseMigrations` or a browser-specific base test case. CI installs
Chromium and its Linux dependencies before running:

```shell
composer test:browser
```

The browser suite is also part of `composer test:push` and the Application
Quality workflow.

## Pest Browser example

```php
<?php

use App\Models\Users\User;

test('an administrator can reach the dashboard', function (): void {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user);

    visit(route('dashboard'))
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});
```

Use named routes and model factories for setup. Keep each browser test focused
on one user-visible behavior and assert that the page has no JavaScript errors.
Prefer stable `data-test` attributes for controls that do not have a durable
accessible label.

## Livewire interactions

Interact with Livewire through the rendered page, not by calling component
methods directly:

```php
test('a user can submit a form in the browser', function (): void {
    visit(route('login'))
        ->fill('@email', 'administrator@example.com')
        ->fill('@password', 'password')
        ->press('@sign-in')
        ->assertPathIs('/dashboard')
        ->assertNoJavascriptErrors();
});
```

Use Feature or Integration tests for the same form's validation and database
effects. The browser test should prove that the controls are wired together
and that the user can complete the journey.

## Smoke coverage

Keep one small smoke test for each critical entry point. A smoke test should
verify the page renders, the expected heading is visible, and there are no
JavaScript errors. Do not retain framework scaffold assertions such as a
generic `/` page example.

## Boundaries

- `tests/Unit`: isolated PHP behavior with no database or browser.
- `tests/Integration`: Eloquent, database, builders, services, and Livewire
  component collaboration.
- `tests/Feature`: HTTP routes, authorization, and complete server-side user
  workflows.
- `tests/Browser`: real browser behavior and critical JavaScript/Livewire
  journeys.

If a test can prove the behavior without a browser, keep it out of
`tests/Browser`.
