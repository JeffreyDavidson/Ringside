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
        ->assertSee('Add User')
        ->assertSeeHtml('placeholder="Search users"')
        ->assertSee('John Admin')
        ->assertSee($administrator->email)
        ->assertSee('(123) 456-7890')
        ->assertSee(Role::Administrator->name)
        ->assertSee('Jane Member')
        ->assertSee($basicUser->email)
        ->assertSee(Role::Basic->name);
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
        'first_name' => 'John',
        'last_name' => 'Smith',
    ]);
    User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'John');

    // Assert
    $component
        ->assertSee('John Smith')
        ->assertDontSee('Jane Doe');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('John Smith')
        ->assertSee('Jane Doe');
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
