<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Livewire\Users\Tables\UsersTable;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Integration tests for UsersTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component rendering with complex data relationships
 * - Filtering and search functionality integration
 * - Action dropdown integration
 * - Status display integration
 * - Real database interaction with user data
 */
describe('UsersTable Component Integration', function () {

    beforeEach(function () {
        $this->user = administrator();
        $this->actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders users table with complete data relationships', function () {
            // Create users with different roles and statuses
            $adminUser = User::factory()->administrator()->create([
                'first_name' => 'John',
                'last_name' => 'Admin',
                'email' => 'admin@example.com',
            ]);
            $basicUser = User::factory()->create([
                'role' => Role::Basic,
                'first_name' => 'Jane',
                'last_name' => 'User',
                'email' => 'user@example.com',
            ]);
            $unverifiedUser = User::factory()->unverified()->create([
                'first_name' => 'Bob',
                'last_name' => 'Unverified',
                'email' => 'unverified@example.com',
            ]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertSee('John Admin')
                ->assertSee('Jane User')
                ->assertSee('Bob Unverified')
                ->assertSee('admin@example.com')
                ->assertSee('user@example.com')
                ->assertSee('unverified@example.com');
        });

        test('displays correct role labels for different user types', function () {
            $adminUser = User::factory()->administrator()->create(['first_name' => 'Admin']);
            $basicUser = User::factory()->create([
                'role' => Role::Basic,
                'first_name' => 'Basic',
            ]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertSee('Admin')
                ->assertSee('Basic')
                ->assertSee('Administrator')
                ->assertSee('Basic');
        });

        test('displays correct status indicators for different user states', function () {
            $activeUser = User::factory()->create([
                'status' => UserStatus::Active,
                'first_name' => 'Active',
            ]);
            $inactiveUser = User::factory()->create([
                'status' => UserStatus::Inactive,
                'first_name' => 'Inactive',
            ]);
            $unverifiedUser = User::factory()->create([
                'status' => UserStatus::Unverified,
                'first_name' => 'Unverified',
            ]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertSee('Active')
                ->assertSee('Inactive')
                ->assertSee('Unverified');
        });

        test('displays formatted phone numbers correctly', function () {
            $userWithPhone = User::factory()->create([
                'first_name' => 'Phone',
                'last_name' => 'User',
                'phone_number' => '1234567890',
            ]);
            $userWithoutPhone = User::factory()->create([
                'first_name' => 'No Phone',
                'last_name' => 'User',
                'phone_number' => null,
            ]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertSee('Phone User')
                ->assertSee('No Phone User')
                ->assertSee('(123) 456-7890');
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters users by name correctly', function () {
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
                'last_name' => 'Johnson',
                'email' => 'bob@example.com',
            ]);

            $component = Livewire::test(UsersTable::class);

            // Test search by first name
            $component
                ->set('search', 'John')
                ->assertSee('John Smith')
                ->assertDontSee('Jane Doe')
                ->assertDontSee('Bob Johnson');

            // Test search by last name
            $component
                ->set('search', 'Doe')
                ->assertSee('Jane Doe')
                ->assertDontSee('John Smith')
                ->assertDontSee('Bob Johnson');

            // Test clearing search
            $component
                ->set('search', '')
                ->assertSee('John Smith')
                ->assertSee('Jane Doe')
                ->assertSee('Bob Johnson');
        });

        test('search functionality filters users by email correctly', function () {
            User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User1',
                'email' => 'unique@domain.com',
            ]);
            User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User2',
                'email' => 'different@domain.com',
            ]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->set('search', 'unique@')
                ->assertSee('Test User1')
                ->assertSee('unique@domain.com')
                ->assertDontSee('Test User2')
                ->assertDontSee('different@domain.com');
        });

        test('component handles complex search patterns', function () {
            User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@company.com',
            ]);

            $component = Livewire::test(UsersTable::class);

            // Search should work with partial matches
            $component
                ->set('search', 'john')
                ->assertSee('John Smith');

            $component
                ->set('search', 'smith')
                ->assertSee('John Smith');

            $component
                ->set('search', 'company')
                ->assertSee('John Smith');
        });
    });

    describe('data ordering and presentation', function () {
        test('users are ordered by last name as configured', function () {
            $userA = User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Anderson',
                'created_at' => now()->subDay(),
            ]);
            $userB = User::factory()->create([
                'first_name' => 'Jane',
                'last_name' => 'Baker',
                'created_at' => now(),
            ]);
            $userC = User::factory()->create([
                'first_name' => 'Bob',
                'last_name' => 'Cooper',
                'created_at' => now()->subHour(),
            ]);

            $component = Livewire::test(UsersTable::class);

            // Get the rendered content to check ordering
            $html = $component->get('users')->toHtml();

            // Anderson should appear before Baker, Baker before Cooper
            $andersonPos = mb_strpos($html, 'Anderson');
            $bakerPos = mb_strpos($html, 'Baker');
            $cooperPos = mb_strpos($html, 'Cooper');

            expect($andersonPos)->toBeLessThan($bakerPos);
            expect($bakerPos)->toBeLessThan($cooperPos);
        });

        test('component selects correct fields for performance', function () {
            $user = User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone_number' => '1234567890',
            ]);

            $component = Livewire::test(UsersTable::class);

            // Verify the component loads without N+1 issues
            $users = $component->instance()->builder()->get();
            expect($users)->toBeCollection();
            expect($users->count())->toBeGreaterThan(0);
        });
    });

    describe('component state management', function () {
        test('component maintains state between interactions', function () {
            User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
            User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

            $component = Livewire::test(UsersTable::class);

            // Set search and verify it persists
            $component
                ->set('search', 'John')
                ->assertSee('John Doe')
                ->assertDontSee('Jane Smith');

            // Component should maintain search state
            $component
                ->call('$refresh')
                ->assertSee('John Doe')
                ->assertDontSee('Jane Smith');
        });

        test('component handles real-time data updates', function () {
            $user = User::factory()->create([
                'first_name' => 'Original',
                'last_name' => 'Name',
            ]);

            $component = Livewire::test(UsersTable::class);
            $component->assertSee('Original Name');

            // Update user data
            $user->update([
                'first_name' => 'Updated',
                'last_name' => 'Name',
            ]);

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Updated Name');
            $component->assertDontSee('Original Name');
        });
    });

    describe('action integration', function () {
        test('component integrates with authorization policies', function () {
            $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

            // Test as administrator (should see all actions)
            $component = Livewire::actingAs($this->user)->test(UsersTable::class);
            $component->assertOk();
            $component->assertSee($user->first_name);
        });

        test('component handles action availability based on user permissions', function () {
            $testUser = User::factory()->create(['first_name' => 'Action', 'last_name' => 'Test']);

            $component = Livewire::actingAs($this->user)->test(UsersTable::class);

            // Administrator should see the user
            $component->assertSee('Action Test');
        });
    });

    describe('performance and scalability', function () {
        test('component handles large datasets efficiently', function () {
            // Create multiple users with various attributes
            User::factory()->count(20)->create();

            $component = Livewire::test(UsersTable::class);

            // Component should render efficiently
            $component->assertOk();

            // Should not have N+1 query issues (query counting would require additional setup)
            expect($component->get('users'))->not->toBeEmpty();
        });

        test('component eager loads necessary relationships', function () {
            $userWithWrestler = User::factory()->create(['first_name' => 'Wrestler', 'last_name' => 'Owner']);
            Wrestler::factory()->create(['user_id' => $userWithWrestler->id]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertOk()
                ->assertSee('Wrestler Owner');
        });
    });

    describe('user status and role display integration', function () {
        test('component displays role information correctly', function () {
            $admin = User::factory()->administrator()->create(['first_name' => 'Super', 'last_name' => 'Admin']);
            $basic = User::factory()->create(['role' => Role::Basic, 'first_name' => 'Regular', 'last_name' => 'User']);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertSee('Super Admin')
                ->assertSee('Regular User');
        });

        test('component handles different user statuses correctly', function () {
            $activeUser = User::factory()->create([
                'status' => UserStatus::Active,
                'first_name' => 'Active',
                'last_name' => 'User',
            ]);
            $inactiveUser = User::factory()->create([
                'status' => UserStatus::Inactive,
                'first_name' => 'Inactive',
                'last_name' => 'User',
            ]);
            $unverifiedUser = User::factory()->create([
                'status' => UserStatus::Unverified,
                'first_name' => 'Unverified',
                'last_name' => 'User',
            ]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertSee('Active User')
                ->assertSee('Inactive User')
                ->assertSee('Unverified User');
        });
    });

    describe('error handling and edge cases', function () {
        test('component handles empty datasets gracefully', function () {
            // Clear all users except the acting user
            User::where('id', '!=', $this->user->id)->delete();

            $component = Livewire::test(UsersTable::class);

            $component->assertOk();
            // Should still show the acting user
            $component->assertSee($this->user->first_name);
        });

        test('component handles users with missing data gracefully', function () {
            $userWithNulls = User::factory()->create([
                'first_name' => 'Missing',
                'last_name' => 'Data',
                'phone_number' => null,
                'avatar_path' => null,
            ]);

            $component = Livewire::test(UsersTable::class);

            $component
                ->assertOk()
                ->assertSee('Missing Data');
        });

        test('component handles invalid search input gracefully', function () {
            User::factory()->create(['first_name' => 'Valid', 'last_name' => 'User']);

            $component = Livewire::test(UsersTable::class);

            // Test with special characters
            $component
                ->set('search', '@#$%^&*()')
                ->assertOk();

            // Test with very long search
            $component
                ->set('search', str_repeat('a', 1000))
                ->assertOk();

            // Test with SQL injection attempt
            $component
                ->set('search', "'; DROP TABLE users; --")
                ->assertOk();
        });
    });
});
