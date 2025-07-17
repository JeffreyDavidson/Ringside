<?php

declare(strict_types=1);

use App\Livewire\Users\Modals\FormModal;
use App\Livewire\Users\UserForm;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('FormModal Configuration', function () {
    it('uses correct user form class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $property = $reflection->getProperty('modelForm');
        $property->setAccessible(true);

        expect($property->getValue($modal))->toBe(UserForm::class);
    });

    it('uses correct model type', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $property = $reflection->getProperty('modelType');
        $property->setAccessible(true);

        expect($property->getValue($modal))->toBe(User::class);
    });
});

describe('FormModal Rendering', function () {
    it('can render in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertOk();
    });

    it('can render in edit mode', function () {
        $user = User::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id);

        $component->assertOk();
    });

    it('displays correct title in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Create User');
    });

    it('displays correct title in edit mode', function () {
        $user = User::factory()->create(['name' => 'Test User']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id);

        $component->assertSee('Edit User');
    });
});

describe('FormModal Create Operations', function () {
    it('can create a new user with valid data', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'john@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('userCreated');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    it('validates required fields when creating', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', '')
            ->set('form.email', '')
            ->set('form.password', '')
            ->call('save');

        $component->assertHasErrors([
            'form.name' => 'required',
            'form.email' => 'required', 
            'form.password' => 'required',
        ]);
    });

    it('validates email format', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'invalid-email')
            ->set('form.password', 'password123')
            ->call('save');

        $component->assertHasErrors(['form.email']);
    });

    it('validates email uniqueness', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'existing@example.com')
            ->set('form.password', 'password123')
            ->call('save');

        $component->assertHasErrors(['form.email']);
    });

    it('validates password confirmation', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'john@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'different-password')
            ->call('save');

        $component->assertHasErrors(['form.password']);
    });

    it('validates minimum password length', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'john@example.com')
            ->set('form.password', '123')
            ->set('form.password_confirmation', '123')
            ->call('save');

        $component->assertHasErrors(['form.password']);
    });

    it('hashes password when creating user', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'john@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('save');

        $user = User::where('email', 'john@example.com')->first();
        expect(Hash::check('password123', $user->password))->toBeTrue();
    });
});

describe('FormModal Edit Operations', function () {
    it('can edit an existing user', function () {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id)
            ->set('form.name', 'Updated Name')
            ->set('form.email', 'updated@example.com')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('userUpdated');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    });

    it('loads existing user data in edit mode', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id);

        $component->assertSet('form.name', 'Test User');
        $component->assertSet('form.email', 'test@example.com');
    });

    it('validates email uniqueness excluding current user when editing', function () {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user2->id)
            ->set('form.email', 'user1@example.com')
            ->call('save');

        $component->assertHasErrors(['form.email']);
    });

    it('allows keeping same email when editing', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id)
            ->set('form.name', 'Updated Name')
            ->set('form.email', 'test@example.com')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('userUpdated');
    });

    it('does not require password when editing', function () {
        $user = User::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id)
            ->set('form.name', 'Updated Name')
            ->call('save');

        $component->assertHasNoErrors();
    });

    it('can update password when editing', function () {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id)
            ->set('form.password', 'newpassword123')
            ->set('form.password_confirmation', 'newpassword123')
            ->call('save');

        $component->assertHasNoErrors();
        
        $user->refresh();
        expect($user->password)->not->toBe($originalPassword);
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });
});

describe('FormModal State Management', function () {
    it('resets form when switching modes', function () {
        $user = User::factory()->create(['name' => 'Test User']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id)
            ->call('createMode');

        $component->assertSet('form.name', null);
        $component->assertSet('form.email', null);
    });

    it('closes modal after successful save', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'john@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('save');

        $component->assertDispatched('closeModal');
    });

    it('keeps modal open when validation fails', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', '')
            ->call('save');

        $component->assertNotDispatched('closeModal');
    });
});

describe('FormModal Role Management', function () {
    it('can assign user role when creating', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'john@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->set('form.role', 'Administrator')
            ->call('save');

        $component->assertHasNoErrors();
        
        $user = User::where('email', 'john@example.com')->first();
        expect($user->role->value)->toBe('Administrator');
    });

    it('can update user role when editing', function () {
        $user = User::factory()->create(['role' => 'User']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $user->id)
            ->set('form.role', 'Administrator')
            ->call('save');

        $component->assertHasNoErrors();
        
        $user->refresh();
        expect($user->role->value)->toBe('Administrator');
    });

    it('validates role enum values', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'John Doe')
            ->set('form.email', 'john@example.com')
            ->set('form.password', 'password123')
            ->set('form.role', 'InvalidRole')
            ->call('save');

        $component->assertHasErrors(['form.role']);
    });
});

describe('FormModal Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertUnauthorized();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertUnauthorized();
    });
});