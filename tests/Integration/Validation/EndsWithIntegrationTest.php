<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;

/**
 * Integration tests for custom ends_with validation rule message formatting.
 *
 * INTEGRATION TEST SCOPE:
 * - Laravel validation framework integration with custom message replacer
 * - Complete validation workflow with error message formatting
 * - Custom validation rule registration and message handling
 * - Validator facade integration with proper grammar formatting
 * - Service provider boot process integration
 *
 * These tests verify that the custom ends_with replacer works correctly
 * within Laravel's validation system with proper message formatting.
 *
 * @see \App\Providers\AppServiceProvider::boot()
 */
describe('EndsWithValidation Integration Tests', function () {
    describe('validation message formatting', function () {
        test('formats single argument correctly', function () {
            // Arrange
            $validator = Validator::make(['name' => 'Hello world'], [
                'name' => 'ends_with:foo',
            ]);

            // Act & Assert
            expect($validator->errors()->first('name'))
                ->toBe('The name field must end with one of the following: foo.');
        });

        test('formats two arguments correctly', function () {
            // Arrange
            $validator = Validator::make(['name' => 'Hello world'], [
                'name' => 'ends_with:foo,bar',
            ]);

            // Act & Assert
            expect($validator->errors()->first('name'))
                ->toBe('The name field must end with one of the following: foo or bar.');
        });

        test('formats multiple arguments with proper grammar', function () {
            // Arrange
            $validator = Validator::make(['name' => 'Hello world'], [
                'name' => 'ends_with:foo,bar,baz',
            ]);

            // Act & Assert
            expect($validator->errors()->first('name'))
                ->toBe('The name field must end with one of the following: foo, bar or baz.');
        });

        test('formats four arguments with correct comma placement', function () {
            // Arrange
            $validator = Validator::make(['name' => 'Hello world'], [
                'name' => 'ends_with:foo,bar,baz,qux',
            ]);

            // Act & Assert
            expect($validator->errors()->first('name'))
                ->toBe('The name field must end with one of the following: foo, bar, baz or qux.');
        });
    });

    describe('validation rule functionality', function () {
        test('passes when value ends with specified string', function () {
            // Arrange
            $validator = Validator::make(['name' => 'Hello world'], [
                'name' => 'ends_with:world',
            ]);

            // Act & Assert
            expect($validator->passes())->toBeTrue();
        });

        test('fails when value does not end with specified string', function () {
            // Arrange
            $validator = Validator::make(['name' => 'Hello world'], [
                'name' => 'ends_with:foo',
            ]);

            // Act & Assert
            expect($validator->fails())->toBeTrue();
        });

        test('passes when value ends with one of multiple options', function () {
            // Arrange
            $validator = Validator::make(['name' => 'Hello world'], [
                'name' => 'ends_with:foo,world,bar',
            ]);

            // Act & Assert
            expect($validator->passes())->toBeTrue();
        });
    });
});
