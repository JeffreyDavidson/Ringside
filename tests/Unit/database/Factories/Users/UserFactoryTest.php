<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;

/**
 * Unit tests for UserFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (administrator, unverified, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the UserFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Users\UserFactory
 */
describe('UserFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates user with correct default attributes', function () {
            // Arrange & Act
            $user = User::factory()->make();
            
            // Assert
            expect($user)->toBeInstanceOf(User::class);
            expect($user->first_name)->toBeString();
            expect($user->first_name)->not->toBeEmpty();
            expect($user->last_name)->toBeString();
            expect($user->last_name)->not->toBeEmpty();
            expect($user->email)->toBeString();
            expect($user->email)->toContain('@');
            expect($user->password)->toBeString();
            expect($user->password)->not->toBeEmpty();
        });

        test('generates realistic user names', function () {
            // Arrange & Act
            $user = User::factory()->make();
            
            // Assert
            expect($user->first_name)->toBeString();
            expect(strlen($user->first_name))->toBeGreaterThan(1);
            expect($user->last_name)->toBeString();
            expect(strlen($user->last_name))->toBeGreaterThan(1);
        });

        test('generates valid email addresses', function () {
            // Arrange & Act
            $user = User::factory()->make();
            
            // Assert
            expect($user->email)->toBeString();
            expect($user->email)->toContain('@');
            expect($user->email)->toContain('.');
            expect(filter_var($user->email, FILTER_VALIDATE_EMAIL))->toBeTruthy();
        });
    });

    describe('factory state methods', function () {
        test('administrator state works correctly', function () {
            // Arrange & Act
            $admin = User::factory()->administrator()->make();
            
            // Assert
            expect($admin->role)->toBe(Role::Administrator);
            expect($admin->isAdministrator())->toBeTrue();
        });

        test('unverified state works correctly', function () {
            // Arrange & Act
            $unverified = User::factory()->unverified()->make();
            
            // Assert
            expect($unverified->status)->toBe(UserStatus::Unverified);
        });

        test('basic user state is default', function () {
            // Arrange & Act
            $user = User::factory()->make();
            
            // Assert
            expect($user->role)->toBe(Role::Basic);
            expect($user->isAdministrator())->toBeFalse();
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $user = User::factory()->make([
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ]);
            
            // Assert
            expect($user->first_name)->toBe('John');
            expect($user->last_name)->toBe('Doe');
            expect($user->email)->toBe('john@example.com');
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $user = User::factory()->make([
                'first_name' => 'Custom',
            ]);
            
            // Assert
            expect($user->first_name)->toBe('Custom');
            expect($user->last_name)->toBeString();
            expect($user->email)->toBeString();
            expect($user->password)->toBeString();
        });
    });

    describe('data consistency', function () {
        test('generates unique email addresses', function () {
            // Arrange & Act
            $user1 = User::factory()->make();
            $user2 = User::factory()->make();
            
            // Assert
            expect($user1->email)->not->toBe($user2->email);
        });

        test('generates consistent data format', function () {
            // Arrange & Act
            $users = collect(range(1, 5))->map(fn() => User::factory()->make());
            
            // Assert
            foreach ($users as $user) {
                expect($user->first_name)->toBeString();
                expect($user->last_name)->toBeString();
                expect($user->email)->toBeString();
                expect($user->email)->toContain('@');
                expect($user->password)->toBeString();
            }
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $user = User::factory()->create();
            
            // Assert
            expect($user->exists)->toBeTrue();
            expect($user->id)->toBeGreaterThan(0);
        });
    });
});
