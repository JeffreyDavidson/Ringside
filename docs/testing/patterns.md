# Common Testing Patterns

## Named Routes Usage
```php
// ✅ CORRECT - Use named routes
actingAs(administrator())
    ->get(route('wrestlers.show', $wrestler))
    ->assertOk();

// ❌ INCORRECT - Hardcoded URLs
actingAs(administrator())
    ->get("/wrestlers/{$wrestler->id}")
    ->assertOk();
```

## Livewire Property Passing
```php
// ✅ CORRECT - Pass properties in initial array
livewire(WrestlerTable::class, ['userId' => $user->id])
    ->assertSee($wrestler->name);

// ❌ INCORRECT - Using set() for required properties
livewire(WrestlerTable::class)
    ->set('userId', $user->id)
    ->assertSee($wrestler->name);
```

## Authorization Testing
```php
describe('authorization patterns', function () {
    test('administrators can access resource', function () {
        actingAs(administrator())
            ->get(route('wrestlers.index'))
            ->assertOk();
    });

    test('basic users cannot access resource', function () {
        actingAs(basicUser())
            ->get(route('wrestlers.index'))
            ->assertForbidden();
    });

    test('guests are redirected to login', function () {
        get(route('wrestlers.index'))
            ->assertRedirect(route('login'));
    });
});
```
