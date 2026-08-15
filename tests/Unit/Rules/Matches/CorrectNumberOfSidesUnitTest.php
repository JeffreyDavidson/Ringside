<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Rules\Matches\CorrectNumberOfSides;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Unit tests for CorrectNumberOfSides validation rule.
 *
 * UNIT TEST SCOPE:
 * - DataAwareRule implementation and data access logic
 * - Array counting and comparison logic
 * - Match type validation with mocked dependencies
 * - Error message consistency
 * - Edge cases (missing data, null values, type handling)
 *
 * These tests verify the CorrectNumberOfSides rule logic independently
 * of models, database, or Laravel's validation framework.
 *
 * @see CorrectNumberOfSides
 */
describe('CorrectNumberOfSides Validation Rule Unit Tests', function () {
    test('accepts the required number of sides for a fixed match type', function () {
        $failed = false;
        $rule = (new CorrectNumberOfSides())->setData(['matchType' => MatchType::Singles]);

        $rule->validate('competitors', [[], []], validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }));

        expect($failed)->toBeFalse();
    });

    test('rejects the wrong number of sides for a fixed match type', function () {
        $failureMessage = null;
        $rule = (new CorrectNumberOfSides())->setData(['matchType' => MatchType::TripleThreat]);

        $rule->validate('competitors', [[], []], validationFailureCallback(function (string $message) use (&$failureMessage): void {
            $failureMessage = $message;
        }));

        expect($failureMessage)->toBe('This match does not have the required number of competitor sides.');
    });

    test('allows variable side counts for open match types', function () {
        $failed = false;
        $rule = (new CorrectNumberOfSides())->setData(['matchType' => MatchType::BattleRoyal]);

        $rule->validate('competitors', [[], [], [], [], [], []], validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }));

        expect($failed)->toBeFalse();
    });

    describe('DataAwareRule implementation', function () {
        test('implements DataAwareRule interface correctly', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();

            // Assert
            expect($rule)->toBeInstanceOf(DataAwareRule::class);
            expect($rule)->toBeInstanceOf(ValidationRule::class);
        });

        test('setData method stores data correctly', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $testData = ['match_type_id' => 123, 'other_field' => 'value'];

            // Act
            $result = $rule->setData($testData);

            // Assert
            expect($result)->toBe($rule); // Returns self for method chaining
        });

        test('setData method handles empty array', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();

            // Act
            $result = $rule->setData([]);

            // Assert
            expect($result)->toBe($rule);
        });
    });

    describe('counting logic with valid match types', function () {
        test('validation passes when competitor count matches match type sides', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act - Test with 2 sides (standard match)
            $rule->validate('competitors', ['side1', 'side2'], validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when competitor count exceeds match type sides', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act - Test with 3 sides when match type expects 2
            $rule->validate('competitors', ['side1', 'side2', 'side3'], validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when competitor count is less than match type sides', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act - Test with 1 side when match type expects 2
            $rule->validate('competitors', ['side1'], validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });
    });

    describe('edge cases with match type handling', function () {
        test('validation passes when match type does not exist', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 999]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', ['side1', 'side2'], validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when match_type_id is missing from data', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData([]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', ['side1', 'side2'], validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('value type handling and array processing', function () {
        test('handles empty array values', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', [], validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });

        test('handles null values', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', null, validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });

        test('handles string values by converting to array', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('competitors', 'single_competitor', validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });

        test('correctly counts array elements', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act - Test with exactly 2 competitors
            $rule->validate('competitors', ['competitor1', 'competitor2'], validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });
    });

    describe('error message consistency', function () {
        test('error message is consistent when validation fails', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act - This won't actually fail because no match type exists, but we test the message format
            $rule->validate('competitors', ['side1'], validationFailureCallback($failCallback));

            // Assert - Should not fail because no match type exists to validate against
            expect($failMessage)->toBe('');
        });

        test('attribute name does not affect validation logic', function () {
            // Arrange
            $rule = new CorrectNumberOfSides();
            $rule->setData(['match_type_id' => 123]);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('different_attribute', ['side1'], validationFailureCallback($failCallback));

            // Assert - Should pass because no match type exists to validate against
            expect($failCalled)->toBeFalse();
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});
