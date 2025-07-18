# Test Structure & Organization

## Arrange-Act-Assert (AAA) Pattern
All tests must follow clear AAA pattern with visual separation:

```php
test('wrestler can be employed successfully', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create(['name' => 'John Doe']);
    $employmentDate = now()->subDays(30);

    // Act
    $action = EmployAction::make()
        ->handle($wrestler, $employmentDate);

    // Assert
    expect($wrestler->fresh()->isEmployed())->toBeTrue();
    expect($wrestler->currentEmployment->started_at)->toEqual($employmentDate);
});
```

## AAA Guidelines
- Use clear comment blocks: `// Arrange`, `// Act`, `// Assert`
- Separate sections with blank lines for readability
- Keep arrange section focused on data setup only
- Act section should contain the primary action being tested
- Assert section should verify expected outcomes
