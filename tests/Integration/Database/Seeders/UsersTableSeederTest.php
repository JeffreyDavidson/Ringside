<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

/**
 * Integration tests for UsersTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - User role distribution validation (administrators and basic users)
 * - User data structure and attribute validation
 * - Data consistency and count verification
 *
 * These tests verify that the UsersTableSeeder correctly populates
 * the database with the required user accounts for development and
 * testing purposes.
 *
 * @see \Database\Seeders\UsersTableSeeder
 */
describe('UsersTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'UsersTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates correct total number of users', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'UsersTableSeeder']);

            // Assert - 2 administrators + 300 basic users = 302 total
            assertDatabaseCount('users', 302);
        });
    });

    describe('administrator users', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'UsersTableSeeder']);
        });

        test('creates correct number of administrator users', function () {
            // Assert
            expect(User::where('role', Role::Administrator)->count())->toBe(2);
        });

        test('creates primary administrator user', function () {
            // Assert
            assertDatabaseHas('users', [
                'first_name' => 'Administrator',
                'last_name' => 'User',
                'email' => 'administrator@example.com',
                'role' => Role::Administrator,
                'status' => UserStatus::Active,
            ]);
        });

        test('creates secondary administrator user', function () {
            // Assert
            assertDatabaseHas('users', [
                'first_name' => 'Second Administrator',
                'last_name' => 'User',
                'email' => 'administrator2@example.com',
                'role' => Role::Administrator,
                'status' => UserStatus::Active,
            ]);
        });

        test('administrator users have required attributes', function () {
            // Arrange
            $administrators = User::where('role', Role::Administrator)->get();

            // Assert
            foreach ($administrators as $admin) {
                expect($admin->first_name)->toBeString();
                expect($admin->last_name)->toBe('User');
                expect($admin->email)->toContain('@example.com');
                expect($admin->avatar_path)->toBe('300-3.png');
                expect($admin->phone_number)->toBeString();
                expect($admin->status)->toBe(UserStatus::Active);
            }
        });
    });

    describe('basic users', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'UsersTableSeeder']);
        });

        test('creates correct number of basic users', function () {
            // Assert
            expect(User::where('role', Role::Basic)->count())->toBe(300);
        });

        test('creates primary basic user', function () {
            // Assert
            assertDatabaseHas('users', [
                'first_name' => 'Basic',
                'last_name' => 'User',
                'email' => 'basic@example.com',
                'role' => Role::Basic,
                'status' => UserStatus::Active,
            ]);
        });

        test('basic users have sequential naming', function () {
            // Arrange
            $basicUsers = User::where('role', Role::Basic)->orderBy('id')->take(5)->get();

            // Assert
            expect($basicUsers[0]->first_name)->toBe('Basic');
            expect($basicUsers[1]->first_name)->toBe('Second Basic');
            expect($basicUsers[2]->first_name)->toBe('Third Basic');
            expect($basicUsers[3]->first_name)->toBe('Fourth Basic');
            expect($basicUsers[4]->first_name)->toBe('Fifth Basic');
        });

        test('basic users have required attributes', function () {
            // Arrange
            $basicUsers = User::where('role', Role::Basic)->take(10)->get();

            // Assert
            foreach ($basicUsers as $user) {
                expect($user->first_name)->toBeString();
                expect($user->last_name)->toBe('User');
                expect($user->email)->toContain('@example.com');
                expect($user->avatar_path)->toBe('300-3.png');
                expect($user->phone_number)->toBeString();
                expect($user->status)->toBe(UserStatus::Active);
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'UsersTableSeeder']);
        });

        test('all users have unique email addresses', function () {
            // Arrange
            $users = User::all();

            // Assert
            expect($users->pluck('email')->unique())->toHaveCount(302);
        });

        test('all users have unique phone numbers', function () {
            // Arrange
            $users = User::all();

            // Assert
            expect($users->pluck('phone_number')->unique())->toHaveCount(302);
        });

        test('all users have valid role assignments', function () {
            // Arrange
            $users = User::all();

            // Assert
            foreach ($users as $user) {
                expect($user->role)->toBeInstanceOf(Role::class);
                expect($user->role)->toBeIn([Role::Administrator, Role::Basic]);
            }
        });
    });
});