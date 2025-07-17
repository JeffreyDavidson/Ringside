<?php

declare(strict_types=1);

use App\Livewire\Users\Forms\Form;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(Form::class)
            ->set('name', '')
            ->set('email', '')
            ->set('password', '')
            ->call('store');

        $form->assertHasErrors([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
    });

    it('validates email format', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->call('store');

        $form->assertHasErrors(['email' => 'email']);
    });

    it('validates email uniqueness', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'existing@example.com')
            ->set('password', 'password123')
            ->call('store');

        $form->assertHasErrors(['email' => 'unique']);
    });

    it('validates password minimum length', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', '123')
            ->call('store');

        $form->assertHasErrors(['password' => 'min']);
    });

    it('validates password confirmation', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different-password')
            ->call('store');

        $form->assertHasErrors(['password' => 'confirmed']);
    });

    it('accepts valid password with confirmation', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Field Validation', function () {
    it('validates name maximum length', function () {
        $longName = str_repeat('a', 256);

        $form = Livewire::test(Form::class)
            ->set('name', $longName)
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->call('store');

        $form->assertHasErrors(['name' => 'max']);
    });

    it('validates email maximum length', function () {
        $longEmail = str_repeat('a', 248) . '@example.com';

        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', $longEmail)
            ->set('password', 'password123')
            ->call('store');

        $form->assertHasErrors(['email' => 'max']);
    });

    it('accepts valid field lengths', function () {
        $validName = str_repeat('a', 255);
        $validEmail = str_repeat('a', 243) . '@example.com';

        $form = Livewire::test(Form::class)
            ->set('name', $validName)
            ->set('email', $validEmail)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();
    });

    it('validates role enum values', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('role', 'InvalidRole')
            ->call('store');

        $form->assertHasErrors(['role' => 'in']);
    });

    it('accepts valid role values', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('role', 'Administrator')
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Store Operations', function () {
    it('can store valid user data', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('role', 'Administrator')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('userCreated');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'Administrator',
        ]);
    });

    it('stores user with minimal required data', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Basic User')
            ->set('email', 'basic@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Basic User',
            'email' => 'basic@example.com',
        ]);
    });

    it('hashes password when storing', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();

        $user = User::where('email', 'john@example.com')->first();
        expect(Hash::check('password123', $user->password))->toBeTrue();
        expect($user->password)->not->toBe('password123');
    });

    it('assigns default role when not specified', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();

        $user = User::where('email', 'john@example.com')->first();
        expect($user->role->value)->toBe('User');
    });

    it('sets user as unverified by default', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();

        $user = User::where('email', 'john@example.com')->first();
        expect($user->email_verified_at)->toBeNull();
    });
});

describe('Form Update Operations', function () {
    it('can update existing user', function () {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'role' => 'User',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $user)
            ->set('name', 'Updated Name')
            ->set('email', 'updated@example.com')
            ->set('role', 'Administrator')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('userUpdated');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'Administrator',
        ]);
    });

    it('validates email uniqueness excluding current user when updating', function () {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $form = Livewire::test(Form::class)
            ->call('setModel', $user2)
            ->set('name', $user2->name)
            ->set('email', 'user1@example.com')
            ->call('update');

        $form->assertHasErrors(['email' => 'unique']);
    });

    it('allows keeping same email when updating', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $user)
            ->set('name', 'Updated Name')
            ->set('email', 'test@example.com')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('userUpdated');
    });

    it('does not require password when updating', function () {
        $user = User::factory()->create();

        $form = Livewire::test(Form::class)
            ->call('setModel', $user)
            ->set('name', 'Updated Name')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('userUpdated');
    });

    it('can update password when provided', function () {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $form = Livewire::test(Form::class)
            ->call('setModel', $user)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('update');

        $form->assertHasNoErrors();
        
        $user->refresh();
        expect($user->password)->not->toBe($originalPassword);
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    it('validates password confirmation when updating password', function () {
        $user = User::factory()->create();

        $form = Livewire::test(Form::class)
            ->call('setModel', $user)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'different-password')
            ->call('update');

        $form->assertHasErrors(['password' => 'confirmed']);
    });

    it('does not change password when not provided', function () {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $form = Livewire::test(Form::class)
            ->call('setModel', $user)
            ->set('name', 'Updated Name')
            ->call('update');

        $form->assertHasNoErrors();
        
        $user->refresh();
        expect($user->password)->toBe($originalPassword);
    });
});

describe('Form Role Management', function () {
    it('can assign administrator role', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Admin User')
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('role', 'Administrator')
            ->call('store');

        $form->assertHasNoErrors();
        
        $user = User::where('email', 'admin@example.com')->first();
        expect($user->role->value)->toBe('Administrator');
        expect($user->isAdministrator())->toBeTrue();
    });

    it('can assign user role', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Regular User')
            ->set('email', 'user@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('role', 'User')
            ->call('store');

        $form->assertHasNoErrors();
        
        $user = User::where('email', 'user@example.com')->first();
        expect($user->role->value)->toBe('User');
        expect($user->isAdministrator())->toBeFalse();
    });

    it('can change user role when updating', function () {
        $user = User::factory()->create(['role' => 'User']);

        $form = Livewire::test(Form::class)
            ->call('setModel', $user)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('role', 'Administrator')
            ->call('update');

        $form->assertHasNoErrors();
        
        $user->refresh();
        expect($user->role->value)->toBe('Administrator');
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('name', null);
        $form->assertSet('email', null);
        $form->assertSet('password', null);
        $form->assertSet('password_confirmation', null);
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('name', 'John Doe');
        $form->assertSet('email', 'invalid-email');
        $form->assertSet('password', 'password123');
    });

    it('loads existing model data correctly', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'Administrator',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $user);

        $form->assertSet('name', 'Test User');
        $form->assertSet('email', 'test@example.com');
        $form->assertSet('role', 'Administrator');
        $form->assertSet('password', null);
        $form->assertSet('password_confirmation', null);
    });

    it('clears password fields when loading existing model', function () {
        $user = User::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('password', 'some-password')
            ->set('password_confirmation', 'some-password')
            ->call('setModel', $user);

        $form->assertSet('password', null);
        $form->assertSet('password_confirmation', null);
    });
});

describe('Form Security', function () {
    it('prevents password from being displayed in form state', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('store');

        $form->assertHasNoErrors();
        
        // Password should not be visible in component state
        $form->assertSet('password', null);
        $form->assertSet('password_confirmation', null);
    });

    it('validates password strength requirements', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'weak')
            ->set('password_confirmation', 'weak')
            ->call('store');

        $form->assertHasErrors(['password' => 'min']);
    });
});