# Factories and Test Data

## Each Test Makes Its Own Data

Create mutable records inside the test that uses them. This keeps setup visible and lets each test select its factory state.

Follow explicit project conventions for shared setup. Prefer configuration in `beforeEach()` and visible scenario-specific records in the test; do not move whole workflows or alternative actor graphs into shared setup to make a test body appear smaller.

## Record Construction

- Use `create()` if the test needs the record in the database.
- Use `make()` only if the test does not need the database. Examples include rendering a notification and testing a value object's behavior.
- Use a named factory state instead of a raw attribute. `User::factory()->unverified()->create()` gives the state meaning; `create(['email_verified_at' => null])` gives only its value.
- Use `for()` or the relationship helper of the project to declare the owner of a record.
- Inspect the factory definition and relationship before using `recycle()`. It reuses a model when the factory graph generates that model; it does not assign an ownership attribute absent from the definition or prevent an eager `create()` in that definition. Keep direct `for($parent)` ownership explicit. Avoid random selection from several parents in tenant-isolation tests.
- Use `count()` and `sequence()` for meaningful repeated records and deterministic attributes. Do not combine distinct lifecycle states or roles just to shorten setup; preserve deliberately foreign fixtures, insertion order and identities used by assertions.

```php
$organization = Organization::factory()->onPlan(BillingPlan::PRO)->create();

$environment = Environment::factory()->for($organization)->create();

// If nested factories generate organizations, recycle this one there.
// Verify that graph before adding recycle($organization).

$organizations = Organization::factory()
    ->count(2)
    ->sequence(
        ['created_at' => now()->setSeconds(30)],
        ['created_at' => now()->setSeconds(1)],
    )
    ->create();
```

Create only the records required to arrange the behavior or support an assertion.

## Datasets

Use a dataset when the setup, test body, and assertions remain the same across input values.

A controller example from this project keeps the two allowed roles on one assertion path:

```php
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\get;

it('owners and managers can prepare public pages', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->scheduled()->create();

    actingAsPromotionMember($promotion, $role);

    // Act
    $response = get(route('events.public-pages.edit', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.publication')
        ->assertViewHas('event', $event);
})->with([
    'owner' => MembershipRole::Owner,
    'manager' => MembershipRole::Manager,
]);
```

Member refusal and foreign-promotion 404 cases have different setup or assertions and belong in separate tests, rather than another column that switches the assertion path.


Use parameterized tests for:

- enum cases
- roles and plans
- boundary values
- input values that are invalid in the same way
- input and output value pairs

Write separate tests if the cases need a different setup, a different behavior, or different assertions. Scenario-dependent branches in one body are several tests disguised as one. Keep accepted and rejected inputs in separate tests; each can have its own homogeneous named dataset. Predicates that inspect an event payload or uniform data are not additional business scenarios by themselves.

Give each dataset case a name that states the difference. A failure then identifies the case without requiring you to count positions.
