<?php

declare(strict_types=1);

use App\Builders\Users\UserBuilder;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Unit tests for UserBuilder query scopes.
 *
 * UNIT TEST SCOPE:
 * - Builder class structure and configuration
 * - Query scope methods (when implemented)
 * - Query builder functionality
 * - Future scope testing foundation
 */
describe('UserBuilder Unit Tests', function () {
    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create(['role' => Role::Basic]);
        $this->unverifiedUser = User::factory()->unverified()->create();
        $this->activeUser = User::factory()->create(['status' => UserStatus::Active]);
        $this->inactiveUser = User::factory()->create(['status' => UserStatus::Inactive]);
    });

    describe('builder class structure', function () {
        test('user model uses custom builder', function () {
            $query = User::query();

            expect($query)->toBeInstanceOf(UserBuilder::class);
        });

        test('builder extends eloquent builder', function () {
            $builder = new UserBuilder(User::query()->getQuery());

            expect($builder)->toBeInstanceOf(Builder::class);
            expect($builder)->toBeInstanceOf(UserBuilder::class);
        });

        test('builder maintains proper generic typing', function () {
            $query = User::query();

            // Verify the builder returns User models
            $user = $query->first();
            if ($user) {
                expect($user)->toBeInstanceOf(User::class);
            }
        });
    });

    describe('basic query functionality', function () {
        test('builder can execute basic queries', function () {
            $users = User::query()->get();

            expect($users)->toBeCollection();
            expect($users->count())->toBeGreaterThan(0);
            expect($users->first())->toBeInstanceOf(User::class);
        });

        test('builder can filter by specific attributes', function () {
            $adminUsers = User::query()->where('role', Role::Administrator)->get();
            $basicUsers = User::query()->where('role', Role::Basic)->get();

            expect($adminUsers)->toBeCollection();
            expect($basicUsers)->toBeCollection();

            foreach ($adminUsers as $user) {
                expect($user->role)->toBe(Role::Administrator);
            }

            foreach ($basicUsers as $user) {
                expect($user->role)->toBe(Role::Basic);
            }
        });

        test('builder can filter by user status', function () {
            $activeUsers = User::query()->where('status', UserStatus::Active)->get();
            $unverifiedUsers = User::query()->where('status', UserStatus::Unverified)->get();

            expect($activeUsers)->toBeCollection();
            expect($unverifiedUsers)->toBeCollection();

            foreach ($activeUsers as $user) {
                expect($user->status)->toBe(UserStatus::Active);
            }

            foreach ($unverifiedUsers as $user) {
                expect($user->status)->toBe(UserStatus::Unverified);
            }
        });
    });

    describe('query builder method chaining', function () {
        test('builder supports method chaining', function () {
            $result = User::query()
                ->where('role', Role::Administrator)
                ->where('status', UserStatus::Active)
                ->first();

            if ($result) {
                expect($result)->toBeInstanceOf(User::class);
                expect($result->role)->toBe(Role::Administrator);
                expect($result->status)->toBe(UserStatus::Active);
            }
        });

        test('builder can combine multiple conditions', function () {
            $adminCount = User::query()
                ->where('role', Role::Administrator)
                ->count();

            $basicCount = User::query()
                ->where('role', Role::Basic)
                ->count();

            expect($adminCount)->toBeGreaterThanOrEqual(1);
            expect($basicCount)->toBeGreaterThanOrEqual(1);
        });

        test('builder supports ordering and limiting', function () {
            $users = User::query()
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            expect($users)->toBeCollection();
            expect($users->count())->toBeLessThanOrEqual(3);

            // Verify ordering (newest first)
            if ($users->count() > 1) {
                expect($users->first()->created_at->gte($users->last()->created_at))->toBeTrue();
            }
        });
    });

    describe('relationship query capabilities', function () {
        test('builder can query with relationships', function () {
            $usersWithWrestlers = User::query()
                ->with('wrestlers')
                ->get();

            expect($usersWithWrestlers)->toBeCollection();

            // Check that the relationship is loaded
            foreach ($usersWithWrestlers as $user) {
                expect($user->relationLoaded('wrestlers'))->toBeTrue();
            }
        });

        test('builder can filter by relationship existence', function () {
            $usersWithWrestlers = User::query()
                ->has('wrestlers')
                ->get();

            $usersWithoutWrestlers = User::query()
                ->doesntHave('wrestlers')
                ->get();

            expect($usersWithWrestlers)->toBeCollection();
            expect($usersWithoutWrestlers)->toBeCollection();
        });

        test('builder can perform whereHas queries', function () {
            $query = User::query()
                ->whereHas('wrestlers', function ($query) {
                    $query->where('name', 'like', '%Test%');
                });

            $users = $query->get();
            expect($users)->toBeCollection();
        });
    });

    describe('future scope foundation', function () {
        test('builder is ready for administrator scope implementation', function () {
            // Test that we can implement administrator scope when needed
            $adminUsers = User::query()
                ->where('role', Role::Administrator)
                ->get();

            expect($adminUsers)->toBeCollection();

            foreach ($adminUsers as $user) {
                expect($user->isAdministrator())->toBeTrue();
            }
        });

        test('builder is ready for active scope implementation', function () {
            // Test that we can implement active scope when needed
            $activeUsers = User::query()
                ->where('status', UserStatus::Active)
                ->get();

            expect($activeUsers)->toBeCollection();

            foreach ($activeUsers as $user) {
                expect($user->status)->toBe(UserStatus::Active);
            }
        });

        test('builder is ready for verified scope implementation', function () {
            // Test that we can implement verified scope when needed
            $verifiedUsers = User::query()
                ->whereIn('status', [UserStatus::Active, UserStatus::Inactive])
                ->get();

            expect($verifiedUsers)->toBeCollection();

            foreach ($verifiedUsers as $user) {
                expect($user->status)->not->toBe(UserStatus::Unverified);
            }
        });

        test('builder could support role-based scopes', function () {
            // Demonstrate future role-based scope capabilities
            $roleBasedQueries = [
                'administrators' => User::query()->where('role', Role::Administrator),
                'basic_users' => User::query()->where('role', Role::Basic),
            ];

            foreach ($roleBasedQueries as $name => $query) {
                expect($query)->toBeInstanceOf(UserBuilder::class);
                expect($query->toSql())->toContain('role');
            }
        });
    });

    describe('query performance and optimization', function () {
        test('builder generates efficient queries', function () {
            $query = User::query()
                ->where('role', Role::Administrator)
                ->where('status', UserStatus::Active);

            $sql = $query->toSql();
            $bindings = $query->getBindings();

            expect($sql)->toContain('role');
            expect($sql)->toContain('status');
            expect($bindings)->toContain('administrator');
            expect($bindings)->toContain('active');
        });

        test('builder handles large datasets efficiently', function () {
            // Create additional test data
            User::factory()->count(10)->create();

            $query = User::query()->limit(5);
            $users = $query->get();

            expect($users->count())->toBeLessThanOrEqual(5);
            expect($query->toSql())->toContain('limit');
        });

        test('builder supports pagination', function () {
            $paginatedUsers = User::query()->paginate(5);

            expect($paginatedUsers)->toBeInstanceOf(LengthAwarePaginator::class);
            expect($paginatedUsers->perPage())->toBe(5);
        });
    });

    describe('builder edge cases and error handling', function () {
        test('builder handles empty result sets', function () {
            $noUsers = User::query()
                ->where('email', 'nonexistent@example.com')
                ->get();

            expect($noUsers)->toBeCollection();
            expect($noUsers->count())->toBe(0);
            expect($noUsers->isEmpty())->toBeTrue();
        });

        test('builder handles invalid enum comparisons gracefully', function () {
            // These queries should not crash even with non-enum values
            $query1 = User::query()->where('role', 'invalid-role');
            $query2 = User::query()->where('status', 'invalid-status');

            expect($query1->get())->toBeCollection();
            expect($query2->get())->toBeCollection();
            expect($query1->count())->toBe(0);
            expect($query2->count())->toBe(0);
        });

        test('builder maintains type safety with enum filtering', function () {
            $administrators = User::query()
                ->where('role', Role::Administrator)
                ->get();

            foreach ($administrators as $admin) {
                expect($admin->role)->toBeInstanceOf(Role::class);
                expect($admin->role)->toBe(Role::Administrator);
            }
        });

        test('builder handles null values appropriately', function () {
            $usersWithoutAvatar = User::query()
                ->whereNull('avatar_path')
                ->get();

            $usersWithoutPhone = User::query()
                ->whereNull('phone_number')
                ->get();

            expect($usersWithoutAvatar)->toBeCollection();
            expect($usersWithoutPhone)->toBeCollection();
        });
    });

    describe('future extensibility', function () {
        test('builder is prepared for additional scopes', function () {
            // Verify the builder can be extended with new methods
            expect(method_exists(UserBuilder::class, '__call'))->toBeTrue();

            // Test that we can add scopes dynamically (Laravel's built-in functionality)
            $query = User::query();
            expect($query)->toBeInstanceOf(UserBuilder::class);
        });

        test('builder maintains Laravel conventions', function () {
            $query = User::query();

            // Verify standard Laravel query builder methods are available
            expect(is_callable([$query, 'where']))->toBeTrue();
            expect(is_callable([$query, 'orderBy']))->toBeTrue();
            expect(is_callable([$query, 'limit']))->toBeTrue();
            expect(is_callable([$query, 'get']))->toBeTrue();
            expect(is_callable([$query, 'first']))->toBeTrue();
            expect(is_callable([$query, 'count']))->toBeTrue();
        });

        test('builder supports custom macro definitions', function () {
            // Test that we could add custom macros to the builder
            expect(is_callable([Builder::class, 'macro']))->toBeTrue();
        });
    });
});
