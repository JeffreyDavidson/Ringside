<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Livewire\Users\Tables\Main;
use App\Models\Users\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('users table', function (): void {
    it('renders the configured table controls and user attributes', function (): void {
        // Arrange
        $administrator = User::factory()->administrator()->create([
            'first_name' => 'John',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
            'phone_number' => '1234567890',
            'status' => UserStatus::Active,
        ]);
        $basicUser = User::factory()->unverified()->create([
            'first_name' => 'Jane',
            'last_name' => 'Member',
            'email' => 'member@example.com',
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Users')
            ->assertSee('Manage global accounts and platform access.')
            ->assertSeeHtml('data-test="index-page-header"')
            ->assertSeeHtml('data-test="table-metadata"')
            ->assertSeeHtml('data-test="table-toolbar"')
            ->assertSee('Add User')
            ->assertSeeHtml('placeholder="Search users"')
            ->assertSee('John Admin')
            ->assertSee($administrator->email)
            ->assertSee('(123) 456-7890')
            ->assertSee(Role::Administrator->name)
            ->assertSee('Jane Member')
            ->assertSee($basicUser->email)
            ->assertSee(Role::Basic->name)
            ->assertDontSee('Remove')
            ->assertDontSeeHtml('wire:click="delete(');
    });

    it('offers each user a labelled row actions menu', function (UserStatus $status, string $statusAction): void {
        // Arrange
        $user = User::factory()->create([
            'first_name' => 'Menu',
            'last_name' => 'Owner',
            'status' => $status,
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSeeHtml('aria-label="Actions for Menu Owner"')
            ->assertSeeHtml('aria-label="User actions"')
            ->assertSeeHtml('href="'.route('users.show', $user).'"')
            ->assertSeeHtml("arguments: { modelId: {$user->id} }")
            ->assertSeeHtml("wire:click=\"changeStatus({$user->id}, '")
            ->assertSee($statusAction);
    })->with([
        'unverified' => [UserStatus::Unverified, 'Activate account'],
        'active' => [UserStatus::Active, 'Deactivate account'],
        'inactive' => [UserStatus::Inactive, 'Reactivate account'],
    ]);

    it('asks for confirmation before deactivating a user account', function (): void {
        // Arrange
        User::factory()->create([
            'first_name' => 'Menu',
            'last_name' => 'Owner',
            'status' => UserStatus::Active,
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSeeHtml('wire:confirm="Deactivate Menu Owner?"');
    });

    it('lets an administrator activate an unverified user account', function (): void {
        $user = User::factory()->unverified()->create();

        livewire(Main::class)
            ->call('changeStatus', $user->id, UserStatus::Active->value)
            ->assertHasNoErrors()
            ->assertDispatched('flash-message', type: 'status', message: 'User account status changed to Active.');

        expect($user->refresh()->status)->toBe(UserStatus::Active)
            ->and($user->email_verified_at)->toBeNull();
    });

    it('lets an administrator deactivate and reactivate user accounts', function (): void {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $component = livewire(Main::class);

        $component->call('changeStatus', $user->id, UserStatus::Inactive->value);

        expect($user->refresh()->status)->toBe(UserStatus::Inactive);

        $component->call('changeStatus', $user->id, UserStatus::Active->value);

        expect($user->refresh()->status)->toBe(UserStatus::Active);
    });

    it('recomputes the remembered status counts after a status change', function (): void {
        // Arrange
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $component = livewire(Main::class);

        // Act
        $component->call('changeStatus', $user->id, UserStatus::Inactive->value);

        // Assert
        expect($component->get('metadataSnapshot.statuses'))->toContain([
            'value' => UserStatus::Inactive->value,
            'label' => UserStatus::Inactive->label(),
            'count' => 1,
        ]);
    });

    it('authorizes before looking up the user', function (): void {
        $component = livewire(Main::class);
        actingAs(basicUser());

        $component
            ->call('changeStatus', PHP_INT_MAX, UserStatus::Inactive->value)
            ->assertForbidden();
    });

    it('does not let the last active administrator be deactivated', function (): void {
        $administrator = User::query()->where('role', Role::Administrator)->firstOrFail();

        livewire(Main::class)
            ->call('changeStatus', $administrator->id, UserStatus::Inactive->value)
            ->assertDispatched('flash-message', type: 'error', message: 'The platform must keep at least one active administrator. Make another user an active administrator first.');

        expect($administrator->refresh()->status)->toBe(UserStatus::Active);
    });

    it('lets an administrator be deactivated when another active administrator remains', function (): void {
        $other = User::factory()->administrator()->create(['status' => UserStatus::Active]);

        livewire(Main::class)
            ->call('changeStatus', $other->id, UserStatus::Inactive->value)
            ->assertDispatched('flash-message', type: 'status', message: 'User account status changed to Inactive.');

        expect($other->refresh()->status)->toBe(UserStatus::Inactive);
    });

    it('rejects invalid user status values', function (): void {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        livewire(Main::class)
            ->call('changeStatus', $user->id, 'suspended')
            ->assertHasErrors('status');

        expect($user->refresh()->status)->toBe(UserStatus::Active);
    });

    it('filters users by status', function (UserStatus $status): void {
        // Arrange
        User::factory()->create([
            'first_name' => 'Matching',
            'last_name' => 'Account',
            'status' => $status,
        ]);
        $hiddenStatus = $status === UserStatus::Active
            ? UserStatus::Inactive
            : UserStatus::Active;
        User::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Account',
            'status' => $hiddenStatus,
        ]);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.status', $status->value);

        // Assert
        $component
            ->assertSee('Matching Account')
            ->assertDontSee('Hidden Account');
    })->with(UserStatus::cases());

    it('searches users by name and clears the search', function (): void {
        // Arrange
        User::factory()->create([
            'first_name' => 'Xylo',
            'last_name' => 'Quartzenberg',
        ]);
        User::factory()->create([
            'first_name' => 'Zephyra',
            'last_name' => 'Vandermolen',
        ]);
        $component = livewire(Main::class);

        // Act
        $component->set('search', 'Xylo');

        // Assert
        $component
            ->assertSee('Xylo Quartzenberg')
            ->assertDontSee('Zephyra Vandermolen');

        // Act
        $component->set('search', '');

        // Assert
        $component
            ->assertSee('Xylo Quartzenberg')
            ->assertSee('Zephyra Vandermolen');
    });

    it('searches users by email', function (): void {
        // Arrange
        $matchingUser = User::factory()->create([
            'first_name' => 'Unique',
            'last_name' => 'Account',
            'email' => 'unique@domain.com',
        ]);
        $hiddenUser = User::factory()->create([
            'first_name' => 'Different',
            'last_name' => 'Account',
            'email' => 'different@domain.com',
        ]);
        $component = livewire(Main::class);

        // Act
        $component->set('search', 'unique@');

        // Assert
        $component
            ->assertSee('Unique Account')
            ->assertSee($matchingUser->email)
            ->assertDontSee('Different Account')
            ->assertDontSee($hiddenUser->email);
    });

    it('orders users by last name', function (): void {
        // Arrange
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Cooper']);
        User::factory()->create(['first_name' => 'John', 'last_name' => 'Anderson']);
        User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Baker']);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSeeInOrder([
            'John Anderson',
            'Jane Baker',
            'Bob Cooper',
        ]);
    });

    it('renders updated user data after a refresh', function (): void {
        // Arrange
        $user = User::factory()->create(['first_name' => 'Original', 'last_name' => 'Name']);
        $component = livewire(Main::class);
        $component->assertSee('Original Name');
        $user->update(['first_name' => 'Updated']);

        // Act
        $component->call('$refresh');

        // Assert
        $component
            ->assertSee('Updated Name')
            ->assertDontSee('Original Name');
    });

    it('renders users without phone numbers', function (): void {
        // Arrange
        User::factory()->create([
            'first_name' => 'Missing',
            'last_name' => 'Phone',
            'phone_number' => null,
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSee('Missing Phone');
    });

    it('treats hostile search input as plain text', function (): void {
        // Arrange
        $user = User::factory()->create(['first_name' => 'Valid', 'last_name' => 'User']);
        $component = livewire(Main::class);

        // Act
        $component->set('search', "'; DROP TABLE users; --");

        // Assert
        $component->assertDontSee('Valid User');
        expect(User::query()->whereKey($user)->exists())->toBeTrue();
    });

    it('forbids users without administrative access', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
