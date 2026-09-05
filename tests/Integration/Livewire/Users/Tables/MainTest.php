<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Livewire\Users\Tables\Main;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('forbids users without administrative access', function (string $actor) {
    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    livewire(Main::class)
        ->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);

describe('UsersTable Component', function () {
    beforeEach(function () {
        $this->administrator = administrator();
        actingAs($this->administrator);
    });

    describe('component rendering integration', function () {
        test('renders the configured table header and search prompt', function () {
            livewire(Main::class)
                ->assertSee('Add User')
                ->assertSeeHtml('placeholder="Search users"');
        });

        test('renders user identity, role, status, and contact data', function () {
            User::factory()->administrator()->create([
                'first_name' => 'John',
                'last_name' => 'Admin',
                'email' => 'admin@example.com',
                'phone_number' => '1234567890',
            ]);
            User::factory()->unverified()->create([
                'role' => Role::Basic,
                'first_name' => 'Jane',
                'last_name' => 'Member',
                'email' => 'member@example.com',
            ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('John Admin')
                ->assertSee('admin@example.com')
                ->assertSee('(123) 456-7890')
                ->assertSee('Administrator')
                ->assertSee('Jane Member')
                ->assertSee('member@example.com')
                ->assertSee('Basic')
                ->assertSee('Unverified');
        });
    });

    describe('filtering and search integration', function () {
        test('status filter returns only users with the selected status', function () {
            $users = [
                UserStatus::Unverified->value => User::factory()->unverified()->create([
                    'first_name' => 'Unverified',
                    'last_name' => 'Account',
                ]),
                UserStatus::Active->value => User::factory()->create([
                    'status' => UserStatus::Active,
                    'first_name' => 'Active',
                    'last_name' => 'Account',
                ]),
                UserStatus::Inactive->value => User::factory()->create([
                    'status' => UserStatus::Inactive,
                    'first_name' => 'Inactive',
                    'last_name' => 'Account',
                ]),
            ];

            foreach ($users as $status => $visibleUser) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee("{$visibleUser->first_name} {$visibleUser->last_name}");

                foreach ($users as $otherStatus => $hiddenUser) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee("{$hiddenUser->first_name} {$hiddenUser->last_name}");
                    }
                }
            }
        });

        test('search functionality filters users by first and last name', function () {
            User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john@example.com',
            ]);
            User::factory()->create([
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
            ]);
            User::factory()->create([
                'first_name' => 'Bob',
                'last_name' => 'Baker',
                'email' => 'bob@example.com',
            ]);

            $component = livewire(Main::class);

            $component
                ->set('search', 'John')
                ->assertSee('John Smith')
                ->assertDontSee('Jane Doe')
                ->assertDontSee('Bob Baker');

            $component
                ->set('search', 'Doe')
                ->assertSee('Jane Doe')
                ->assertDontSee('John Smith')
                ->assertDontSee('Bob Baker');

            $component
                ->set('search', '')
                ->assertSee('John Smith')
                ->assertSee('Jane Doe')
                ->assertSee('Bob Baker');
        });

        test('search functionality filters users by email', function () {
            User::factory()->create([
                'first_name' => 'Unique',
                'last_name' => 'Account',
                'email' => 'unique@domain.com',
            ]);
            User::factory()->create([
                'first_name' => 'Different',
                'last_name' => 'Account',
                'email' => 'different@domain.com',
            ]);

            livewire(Main::class)
                ->set('search', 'unique@')
                ->assertSee('Unique Account')
                ->assertSee('unique@domain.com')
                ->assertDontSee('Different Account')
                ->assertDontSee('different@domain.com');
        });
    });

    describe('data ordering and state management', function () {
        test('builder orders users by last name', function () {
            $users = collect([
                User::factory()->create(['first_name' => 'John', 'last_name' => 'Anderson']),
                User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Baker']),
                User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Cooper']),
            ]);

            $lastNames = app(Main::class)
                ->builder()
                ->whereKey($users->pluck('id')->all())
                ->pluck('last_name')
                ->all();

            expect($lastNames)->toBe(['Anderson', 'Baker', 'Cooper']);
        });

        test('component reflects user data updates', function () {
            $user = User::factory()->create(['first_name' => 'Original', 'last_name' => 'Name']);

            $component = livewire(Main::class);
            $component->assertSee('Original Name');

            $user->update(['first_name' => 'Updated']);

            $component->call('$refresh');
            $component
                ->assertSee('Updated Name')
                ->assertDontSee('Original Name');
        });
    });

    describe('edge cases', function () {
        test('renders the acting administrator when no other users exist', function () {
            User::query()
                ->whereKeyNot($this->administrator->id)
                ->delete();

            livewire(Main::class)
                ->assertSee("{$this->administrator->first_name} {$this->administrator->last_name}");
        });

        test('renders users without phone numbers', function () {
            User::factory()->create([
                'first_name' => 'Missing',
                'last_name' => 'Phone',
                'phone_number' => null,
            ]);

            livewire(Main::class)
                ->assertSee('Missing Phone');
        });

        test('treats hostile search input as plain text', function () {
            $user = User::factory()->create(['first_name' => 'Valid', 'last_name' => 'User']);

            livewire(Main::class)
                ->set('search', "'; DROP TABLE users; --")
                ->assertDontSee('Valid User');

            expect(User::query()->whereKey($user)->exists())->toBeTrue();
        });
    });
});
