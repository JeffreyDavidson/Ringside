<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Livewire\Users\Modals\FormModal;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized user form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the user form fields', function () {
        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.users.modals.form-modal');
        $modal
            ->assertPropertyWired('form.first_name')
            ->assertPropertyWired('form.last_name')
            ->assertPropertyWired('form.email')
            ->assertPropertyWired('form.role')
            ->assertPropertyWired('form.password')
            ->assertPropertyWired('form.password_confirmation');
    });

    it('opens an empty form for creating a user', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.email', '')
            ->assertSet('form.role', Role::Basic->value)
            ->assertSet('form.password', '')
            ->assertSet('form.password_confirmation', '')
            ->assertSee('Create User');
    });

    it('loads an existing user without exposing the password', function () {
        $user = User::factory()->administrator()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $user->id);
        $modal->set('form.first_name', 'Jane');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.first_name', 'Jane')
            ->assertSet('form.last_name', 'Smith')
            ->assertSet('form.email', 'jane@example.com')
            ->assertSet('form.role', Role::Administrator->value)
            ->assertSet('form.password', '')
            ->assertSet('form.password_confirmation', '')
            ->assertSee('Edit User');
    });

    it('propagates a missing user failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates a user with its credentials and role', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'John',
            'form.last_name' => 'Doe',
            'form.email' => 'john@example.com',
            'form.role' => Role::Administrator->value,
            'form.password' => 'password123',
            'form.password_confirmation' => 'password123',
        ]);
        $modal->call('save');

        $user = User::query()->whereEmail('john@example.com')->firstOrFail();
        expect($user->first_name)->toBe('John')
            ->and($user->last_name)->toBe('Doe')
            ->and($user->role)->toBe(Role::Administrator)
            ->and(Hash::check('password123', $user->password))->toBeTrue();
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('userCreated')
            ->assertDispatched('form-submitted')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false)
            ->assertSet('form.first_name', '')
            ->assertSet('form.email', '');
    });

    it('updates a user without changing an omitted password', function () {
        $user = User::factory()->basicUser()->create([
            'first_name' => 'Original',
            'last_name' => 'User',
            'email' => 'original@example.com',
            'password' => 'original-password',
        ]);
        $originalPassword = $user->password;
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $user->id);
        $modal->set([
            'form.first_name' => 'Updated',
            'form.last_name' => 'Administrator',
            'form.email' => 'updated@example.com',
            'form.role' => Role::Administrator->value,
        ]);
        $modal->call('save');

        $user->refresh();
        expect($user->first_name)->toBe('Updated')
            ->and($user->last_name)->toBe('Administrator')
            ->and($user->email)->toBe('updated@example.com')
            ->and($user->role)->toBe(Role::Administrator)
            ->and($user->password)->toBe($originalPassword);
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('userUpdated')
            ->assertSet('isModalOpen', false);
    });

    it('updates a user password when a confirmed replacement is provided', function () {
        $user = User::factory()->create(['password' => 'original-password']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $user->id);
        $modal->set([
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        $modal->call('save');

        expect(Hash::check('replacement-password', $user->refresh()->password))->toBeTrue();
        $modal->assertHasNoErrors();
    });

    it('allows a user to retain their current email address', function () {
        $user = User::factory()->create(['email' => 'current@example.com']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $user->id);
        $modal->call('save');

        $modal->assertHasNoErrors();
        expect($user->refresh()->email)->toBe('current@example.com');
    });

    it('requires complete account data when creating a user', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('save');

        $modal
            ->assertHasErrors([
                'form.first_name' => 'required',
                'form.last_name' => 'required',
                'form.email' => 'required',
                'form.password' => 'required',
                'form.password_confirmation' => 'required',
            ])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(User::query()->count())->toBe(1);
    });

    it('rejects invalid user field values', function (string $case) {
        User::factory()->create(['email' => 'existing@example.com']);
        [$field, $value, $errorField, $rule] = match ($case) {
            'long first name' => ['form.first_name', str_repeat('a', 256), 'form.first_name', 'max'],
            'long last name' => ['form.last_name', str_repeat('a', 256), 'form.last_name', 'max'],
            'invalid email' => ['form.email', 'not-an-email', 'form.email', 'email'],
            'duplicate email' => ['form.email', 'existing@example.com', 'form.email', 'unique'],
            'invalid role' => ['form.role', 'owner', 'form.role', 'in'],
            'short password' => ['form.password', 'short', 'form.password', 'min'],
            'unconfirmed password' => ['form.password_confirmation', 'different-password', 'form.password', 'confirmed'],
            default => throw new InvalidArgumentException("Unknown validation case: {$case}"),
        };
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Valid',
            'form.last_name' => 'User',
            'form.email' => 'valid@example.com',
            'form.role' => Role::Basic->value,
            'form.password' => 'password123',
            'form.password_confirmation' => 'password123',
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$errorField => $rule]);
        expect(User::query()->whereEmail('valid@example.com')->doesntExist())->toBeTrue();
    })->with([
        'long first name',
        'long last name',
        'invalid email',
        'duplicate email',
        'invalid role',
        'short password',
        'unconfirmed password',
    ]);

    it('rejects another user email while editing', function () {
        User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create(['email' => 'current@example.com']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $user->id);
        $modal->set('form.email', 'existing@example.com');
        $modal->call('save');

        $modal->assertHasErrors(['form.email' => 'unique']);
        expect($user->refresh()->email)->toBe('current@example.com');
    });

    it('clears entered data when the modal is closed', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Entered',
            'form.email' => 'entered@example.com',
        ]);
        $modal->call('closeModal');

        $modal
            ->assertSet('isModalOpen', false)
            ->assertSet('form.first_name', '')
            ->assertSet('form.email', '');
    });

    it('resets edited user data when reopening in create mode', function () {
        $user = User::factory()->administrator()->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $user->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.email', '')
            ->assertSet('form.role', Role::Basic->value);
    });

    it('generates valid dummy data that can create a user', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('userCreated')
            ->assertSet('isModalOpen', false);
        expect(User::query()->count())->toBe(2);
    });
});

it('forbids users without administrative access from opening the user form', function (string $actor, string $operation) {
    $user = $operation === 'update' ? User::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal', $user?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
