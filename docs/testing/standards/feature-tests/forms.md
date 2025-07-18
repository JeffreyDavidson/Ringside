# Form Testing

## Form Testing Standards

### Form Submission Testing
```php
describe('form submissions', function () {
    test('valid form submission succeeds', function () {
        // Arrange
        $admin = administrator();
        $formData = [
            'name' => 'Test Wrestler',
            'hometown' => 'Test City',
            'weight' => 200,
        ];

        // Act
        $response = actingAs($admin)
            ->post(route('wrestlers.store'), $formData);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');

        expect(Wrestler::where('name', 'Test Wrestler')->exists())->toBeTrue();
    });

    test('invalid form submission returns errors', function () {
        // Arrange
        $admin = administrator();
        $formData = ['name' => '']; // Missing required field

        // Act
        $response = actingAs($admin)
            ->post(route('wrestlers.store'), $formData);

        // Assert
        $response->assertSessionHasErrors(['name']);
    });
});
```

### File Upload Testing
```php
test('file upload works correctly', function () {
    // Arrange
    Storage::fake('public');
    $admin = administrator();
    $file = UploadedFile::fake()->image('avatar.jpg');

    // Act
    $response = actingAs($admin)
        ->post(route('wrestlers.store'), [
            'name' => 'Test Wrestler',
            'avatar' => $file,
        ]);

    // Assert
    $response->assertRedirect();
    Storage::disk('public')->assertExists('avatars/' . $file->hashName());
});
```

### Validation Error Testing
```php
test('validation errors are displayed correctly', function () {
    // Arrange
    $admin = administrator();

    // Act
    $response = actingAs($admin)
        ->post(route('wrestlers.store'), [
            'name' => '', // Invalid
            'weight' => 'invalid', // Invalid type
        ]);

    // Assert
    $response->assertSessionHasErrors(['name', 'weight']);
    $response->assertRedirect();
});
```
