<?php

declare(strict_types=1);

use App\Rules\Matches\CompetitorsNotDuplicated;

/**
 * Unit tests for CompetitorsNotDuplicated validation rule.
 *
 * UNIT TEST SCOPE:
 * - Array processing and duplication detection logic
 * - Rule logic validation in complete isolation
 * - Data structure handling and edge cases
 * - Error callback testing and message verification
 * - Input validation and type handling
 *
 * These tests verify the CompetitorsNotDuplicated rule logic independently
 * of models, database, or Laravel's validation framework.
 *
 * @see \App\Rules\Matches\CompetitorsNotDuplicated
 */
describe('CompetitorsNotDuplicated Validation Rule Unit Tests', function () {
    describe('wrestler duplication detection', function () {
        test('validation passes with unique wrestlers', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [1, 2]],
                ['wrestlers' => [3, 4]],
            ];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation fails with duplicate wrestlers', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [1, 2]],
                ['wrestlers' => [2, 3]], // Wrestler 2 appears twice
            ];
            
            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toBe('The same wrestler cannot compete multiple times in this match.');
        });

        test('validation fails with duplicate within same side', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [1, 1, 2]], // Wrestler 1 appears twice in same side
                ['wrestlers' => [3, 4]],
            ];
            
            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toBe('The same wrestler cannot compete multiple times in this match.');
        });
    });

    describe('tag team duplication detection', function () {
        test('validation passes with unique tag teams', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['tagteams' => [1, 2]],
                ['tagteams' => [3, 4]],
            ];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation fails with duplicate tag teams', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['tagteams' => [1, 2]],
                ['tagteams' => [2, 3]], // Tag team 2 appears twice
            ];
            
            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toBe('The same tag team cannot compete multiple times in this match.');
        });
    });

    describe('mixed competitor validation', function () {
        test('validation passes with unique mixed competitors', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [1, 2], 'tagteams' => [1]],
                ['wrestlers' => [3], 'tagteams' => [2, 3]],
            ];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validates wrestlers and tag teams independently', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [1], 'tagteams' => [1]],
                ['wrestlers' => [2], 'tagteams' => [2]],
            ];
            // Wrestler 1 and tag team 1 can coexist (different types)
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('data structure handling', function () {
        test('handles empty competitors array', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('handles competitors with only wrestlers key', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [1, 2]],
                ['wrestlers' => [3, 4]],
                // No tagteams key
            ];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('handles competitors with only tagteams key', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['tagteams' => [1, 2]],
                ['tagteams' => [3, 4]],
                // No wrestlers key
            ];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('handles empty wrestler and tag team arrays', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [], 'tagteams' => []],
                ['wrestlers' => [], 'tagteams' => []],
            ];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('edge cases and type handling', function () {
        test('handles null value gracefully', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', null, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('handles string value gracefully', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', 'invalid', $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('handles integer value gracefully', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', 123, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('array flattening logic', function () {
        test('correctly flattens nested arrays', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [[1, 2], [3]]],  // Nested arrays
                ['wrestlers' => [4]],
            ];
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('detects duplicates after flattening', function () {
            // Arrange
            $rule = new CompetitorsNotDuplicated();
            $competitors = [
                ['wrestlers' => [[1, 2], [3]]],  // Nested arrays: [1, 2, 3]
                ['wrestlers' => [2]],            // Duplicate of 2
            ];
            
            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('competitors', $competitors, $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toBe('The same wrestler cannot compete multiple times in this match.');
        });
    });
});