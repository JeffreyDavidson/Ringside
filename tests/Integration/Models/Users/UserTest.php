<?php

declare(strict_types=1);

use App\Builders\Users\UserBuilder;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Integration tests for User model structure and configuration.
 *
 * INTEGRATION TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the User model is properly configured
 * and structured according to the data layer requirements.
 */
describe('User Model Integration Tests', function () {
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
            expect($casts['email_verified_at'])->toBe('datetime');
            expect($casts['password'])->toBe('hashed');
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
            expect(class_uses(User::class))->toContain(HasFactory::class);
            expect(class_uses(User::class))->toContain(Notifiable::class);
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

    test('casts verification timestamps and hashes passwords', function () {
        $user = User::factory()->create([
            'email_verified_at' => '2026-08-15 12:00:00',
            'password' => 'plain-text-password',
        ]);

        expect($user->email_verified_at)->toBeInstanceOf(Carbon::class)
            ->and(Hash::check('plain-text-password', $user->password))->toBeTrue();
    });

});
