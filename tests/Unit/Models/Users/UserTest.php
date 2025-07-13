<?php

declare(strict_types=1);

use App\Builders\Users\UserBuilder;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

/**
 * Unit tests for User model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the User model is properly configured
 * and structured according to the data layer requirements.
 */
describe('User Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $user = new User();
            expect($user->getTable())->toBe('users');
        });

        test('has correct fillable properties', function () {
            $user = new User();

            expect($user->getFillable())->toEqual([
                'first_name',
                'last_name',
                'email',
                'email_verified_at',
                'password',
                'role',
                'status',
                'avatar_path',
                'phone_number',
            ]);
        });

        test('has correct casts configuration', function () {
            $user = new User();
            $casts = $user->getCasts();

            expect($casts['role'])->toBe(Role::class);
            expect($casts['status'])->toBe(UserStatus::class);
        });

        test('has custom eloquent builder', function () {
            $user = new User();
            expect($user->query())->toBeInstanceOf(UserBuilder::class);
        });

        test('has correct default values', function () {
            $user = new User();
            expect($user->status)->toBe(UserStatus::Unverified);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(User::class)->usesTrait(HasFactory::class);
            expect(User::class)->usesTrait(Notifiable::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(User::class);

            // User model implements no custom interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(User::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === User::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has required relationship methods', function () {
            $user = new User();

            expect(method_exists($user, 'isAdministrator'))->toBeTrue();
            expect(method_exists($user, 'getAvatar'))->toBeTrue();
            expect(method_exists($user, 'wrestlers'))->toBeTrue();
        });
    });
});
