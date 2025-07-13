<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Rules\Events\DateCanBeChanged;

/**
 * Unit tests for DateCanBeChanged validation rule.
 *
 * UNIT TEST SCOPE:
 * - Rule logic validation in complete isolation
 * - Direct rule method testing without Laravel validation system
 * - Mock object behavior with different event states
 * - Error callback testing and message verification
 * - Edge cases (null events, method availability)
 *
 * These tests verify the DateCanBeChanged rule logic independently
 * of models, database, or Laravel's validation framework.
 *
 * @see \App\Rules\Events\DateCanBeChanged
 */
describe('DateCanBeChanged Validation Rule Unit Tests', function () {
    describe('rule logic with event instances', function () {
        test('validation passes when event has future date', function () {
            // Arrange
            $futureEvent = \Mockery::mock(Event::class);
            $futureEvent->shouldReceive('hasPastDate')->andReturn(false);
            
            $rule = new DateCanBeChanged($futureEvent);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('date', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation fails when event has past date', function () {
            // Arrange
            $pastEvent = \Mockery::mock(Event::class);
            $pastEvent->shouldReceive('hasPastDate')->andReturn(true);
            
            $rule = new DateCanBeChanged($pastEvent);
            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('date', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toBe('Cannot change the date of an event that has already occurred.');
        });

        test('validation passes when no event provided', function () {
            // Arrange
            $rule = new DateCanBeChanged(null);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('date', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('rule construction and data handling', function () {
        test('rule can be constructed with event', function () {
            // Arrange
            $event = \Mockery::mock(Event::class);

            // Act
            $rule = new DateCanBeChanged($event);

            // Assert
            expect($rule)->toBeInstanceOf(DateCanBeChanged::class);
        });

        test('rule can be constructed with null', function () {
            // Act
            $rule = new DateCanBeChanged(null);

            // Assert
            expect($rule)->toBeInstanceOf(DateCanBeChanged::class);
        });

        test('rule handles various date value types', function () {
            // Arrange
            $futureEvent = \Mockery::mock(Event::class);
            $futureEvent->shouldReceive('hasPastDate')->andReturn(false);
            $rule = new DateCanBeChanged($futureEvent);
            
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act & Assert - string date
            $rule->validate('date', '2024-12-25', $failCallback);
            expect($failCalled)->toBeFalse();

            // Act & Assert - Carbon instance
            $rule->validate('date', now()->addWeek(), $failCallback);
            expect($failCalled)->toBeFalse();
        });
    });

    describe('interface compliance', function () {
        test('rule implements ValidationRule interface', function () {
            // Arrange
            $rule = new DateCanBeChanged(null);

            // Assert
            expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
        });

        test('validate method signature matches interface', function () {
            // Arrange
            $rule = new DateCanBeChanged(null);
            $reflection = new \ReflectionMethod($rule, 'validate');

            // Assert
            expect($reflection->getParameters())->toHaveCount(3);
            expect($reflection->getParameters()[0]->getName())->toBe('attribute');
            expect($reflection->getParameters()[1]->getName())->toBe('value');
            expect($reflection->getParameters()[2]->getName())->toBe('fail');
        });
    });

    describe('error message consistency', function () {
        test('error message is consistent across calls', function () {
            // Arrange
            $pastEvent = \Mockery::mock(Event::class);
            $pastEvent->shouldReceive('hasPastDate')->andReturn(true);
            $rule = new DateCanBeChanged($pastEvent);
            
            $messages = [];
            $failCallback = function (string $message) use (&$messages) {
                $messages[] = $message;
            };

            // Act
            $rule->validate('date', now()->addWeek(), $failCallback);
            $rule->validate('different_field', now()->addMonth(), $failCallback);

            // Assert
            expect($messages)->toHaveCount(2);
            expect($messages[0])->toBe($messages[1]);
            expect($messages[0])->toBe('Cannot change the date of an event that has already occurred.');
        });

        test('attribute name does not affect validation logic', function () {
            // Arrange
            $pastEvent = \Mockery::mock(Event::class);
            $pastEvent->shouldReceive('hasPastDate')->andReturn(true);
            $rule = new DateCanBeChanged($pastEvent);
            
            $failCallCount = 0;
            $failCallback = function () use (&$failCallCount) {
                $failCallCount++;
            };

            // Act
            $rule->validate('date', now(), $failCallback);
            $rule->validate('event_date', now(), $failCallback);
            $rule->validate('scheduled_date', now(), $failCallback);

            // Assert
            expect($failCallCount)->toBe(3);
        });
    });

    afterEach(function () {
        \Mockery::close();
    });
});