<?php

declare(strict_types=1);

use App\Data\Users\UserData;
use App\Enums\Users\Role;
use App\Livewire\Users\Forms\CreateEditForm;
use App\Models\Users\User;
use Illuminate\Validation\ValidationException;
use JMac\Testing\Double;
use Livewire\Component;

describe('user create and edit form', function (): void {
    it('maps user fields to typed application data', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Jane';
        $form->last_name = 'Smith';
        $form->email = 'jane@example.com';
        $form->role = Role::Administrator->value;
        $form->password = 'secure-password';

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(UserData::class)
            ->and($data->firstName)->toBe('Jane')
            ->and($data->lastName)->toBe('Smith')
            ->and($data->email)->toBe('jane@example.com')
            ->and($data->role)->toBe(Role::Administrator)
            ->and($data->password)->toBe('secure-password');
    });

    it('maps a blank password to null', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'John';
        $form->last_name = 'Doe';
        $form->email = 'john@example.com';
        $form->role = Role::Basic->value;
        $form->password = '';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->password)->toBeNull();
    });

    it('hydrates and resolves a user without exposing password values', function (): void {
        // Arrange
        $user = User::factory()->administrator()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
        ]);
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->password = 'stale-password';
        $form->password_confirmation = 'stale-password';

        // Act
        $form->setModel($user);
        $selectedUser = $form->user();

        // Assert
        expect($form->modelId)->toBe($user->id)
            ->and($form->first_name)->toBe('Jane')
            ->and($form->last_name)->toBe('Smith')
            ->and($form->email)->toBe('jane@example.com')
            ->and($form->role)->toBe(Role::Administrator->value)
            ->and($form->password)->toBeEmpty()
            ->and($form->password_confirmation)->toBeEmpty()
            ->and($selectedUser->is($user))->toBeTrue();
    });
});

describe('user form email uniqueness', function (): void {
    it('rejects an email that only differs by case from another user', function (): void {
        // Arrange
        User::factory()->create(['email' => 'taken@example.com']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Jane';
        $form->last_name = 'Smith';
        $form->email = 'Taken@Example.com';
        $form->role = Role::Basic->value;
        $form->password = 'secure-password';
        $form->password_confirmation = 'secure-password';

        // Act
        $errorKeys = [];
        try {
            $form->validate();
        } catch (ValidationException $exception) {
            $errorKeys = array_keys($exception->errors());
        }

        // Assert
        expect($errorKeys)->toBe(['form.email']);
    });

    it('lets a user keep their own email when editing', function (): void {
        // Arrange
        $user = User::factory()->create(['email' => 'mine@example.com']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->setModel($user);
        $form->email = 'Mine@Example.com';

        // Act
        $validated = $form->validate();

        // Assert
        expect($validated['email'])->toBe('Mine@Example.com');
    });
});
