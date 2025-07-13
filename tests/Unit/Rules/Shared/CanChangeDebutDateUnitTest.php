<?php

declare(strict_types=1);

use App\Rules\Shared\CanChangeDebutDate;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit tests for CanChangeDebutDate validation rule.
 *
 * UNIT TEST SCOPE:
 * - Method existence checking logic (isCurrentlyActive, wasActiveOn methods)
 * - Activity status validation with mocked dependencies
 * - Date parsing and comparison logic
 * - Model name resolution strategies (getDisplayName, name property, class_basename)
 * - Error message formatting with dynamic model names
 * - Edge cases (null models, missing methods, various date formats)
 *
 * These tests verify the CanChangeDebutDate rule logic independently
 * of models, database, or Laravel's validation framework.
 *
 * @see \App\Rules\Shared\CanChangeDebutDate
 */
describe('CanChangeDebutDate Validation Rule Unit Tests', function () {
    describe('model validation with activity methods', function () {
        test('validation passes when model is not currently active', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isCurrentlyActive')->andReturn(false);
            $model->shouldReceive('wasActiveOn')->andReturn(true);

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation fails when model is currently active and was not active on target date', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Championship Belt' : null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('debuted_at', now()->addMonth(), $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toBe('The debut date cannot be changed while Championship Belt is currently active.');
        });

        test('validation passes when model is currently active and was active on target date', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return true; }
                public function getAttribute($key) { return $key === 'name' ? 'Championship Belt' : null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addMonth(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when model is currently active but lacks wasActiveOn method', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isCurrentlyActive')->andReturn(true);
            $model->shouldReceive('getAttribute')->with('name')->andReturn('Test Model');

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('method existence checking logic', function () {
        test('validation passes when model lacks isCurrentlyActive method', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            // Note: No isCurrentlyActive method mocked - simulates method_exists() returning false

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when model lacks wasActiveOn method', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isCurrentlyActive')->andReturn(true);
            // Note: No wasActiveOn method mocked - simulates method_exists() returning false

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when model lacks both activity methods', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            // Note: No activity methods mocked - simulates method_exists() returning false for both

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when no model provided', function () {
            // Arrange
            $rule = new CanChangeDebutDate(null);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('model name resolution strategies', function () {
        test('uses getDisplayName when available', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getDisplayName() { return 'Custom Display Name'; }
                public function getAttribute($key) { return $key === 'name' ? 'Regular Name' : null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failMessage)->toContain('Custom Display Name');
        });

        test('uses name property when getDisplayName not available', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Model Name Property' : null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failMessage)->toContain('Model Name Property');
        });

        test('uses class basename when no name methods available', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failMessage)->toMatch('/The debut date cannot be changed while .+ is currently active\./');
        });
    });

    describe('date parsing and handling', function () {
        test('handles Carbon date instances', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Model' : null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
        });

        test('handles string date values', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Model' : null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', '2024-12-25', $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
        });

        test('correctly passes parsed date to wasActiveOn method', function () {
            // Arrange
            $targetDate = now()->addWeek();
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isCurrentlyActive')->andReturn(true);
            $model->shouldReceive('wasActiveOn')
                  ->with(\Mockery::on(function ($date) use ($targetDate) {
                      return $date instanceof \Illuminate\Support\Carbon &&
                             $date->equalTo($targetDate);
                  }))
                  ->andReturn(true);

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', $targetDate, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('interface compliance', function () {
        test('rule implements ValidationRule interface', function () {
            // Arrange
            $rule = new CanChangeDebutDate(null);

            // Assert
            expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
        });

        test('validate method signature matches interface', function () {
            // Arrange
            $rule = new CanChangeDebutDate(null);
            $reflection = new \ReflectionMethod($rule, 'validate');

            // Assert
            expect($reflection->getParameters())->toHaveCount(3);
            expect($reflection->getParameters()[0]->getName())->toBe('attribute');
            expect($reflection->getParameters()[1]->getName())->toBe('value');
            expect($reflection->getParameters()[2]->getName())->toBe('fail');
        });
    });

    describe('error message consistency', function () {
        test('error message format is consistent across different models', function () {
            // Arrange
            $model1 = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'First Title' : null; }
            };
            $model2 = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Second Title' : null; }
            };
            $messages = [];
            $failCallback = function (string $message) use (&$messages) {
                $messages[] = $message;
            };
            // Act
            (new CanChangeDebutDate($model1))->validate('debuted_at', now(), $failCallback);
            (new CanChangeDebutDate($model2))->validate('debuted_at', now(), $failCallback);
            // Assert
            expect($messages)->toHaveCount(2);
            expect($messages[0])->toBe('The debut date cannot be changed while First Title is currently active.');
            expect($messages[1])->toBe('The debut date cannot be changed while Second Title is currently active.');
        });
        test('attribute name does not affect validation logic', function () {
            // Arrange
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Model' : null; }
            };
            $rule = new CanChangeDebutDate($model);
            $failCallCount = 0;
            $failCallback = function () use (&$failCallCount) {
                $failCallCount++;
            };
            // Act
            $rule->validate('debuted_at', now(), $failCallback);
            $rule->validate('debut_date', now(), $failCallback);
            $rule->validate('introduced_at', now(), $failCallback);
            // Assert
            expect($failCallCount)->toBe(3);
        });
    });

    describe('combined validation logic edge cases', function () {
        test('validation handles complex method existence scenarios', function () {
            // Arrange - Model has isCurrentlyActive but not wasActiveOn
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isCurrentlyActive')->andReturn(true);
            // Note: wasActiveOn method not mocked to simulate method_exists() returning false

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse(); // Should pass when wasActiveOn method doesn't exist
        });

        test('validation correctly evaluates combined boolean conditions', function () {
            // Arrange - Model is active AND was not active on target date (both conditions true for failure)
            $model = new class extends Model {
                public function isCurrentlyActive() { return true; }
                public function wasActiveOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Title' : null; }
            };

            $rule = new CanChangeDebutDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('debuted_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeTrue(); // Should fail when both conditions are met
        });
    });

    afterEach(function () {
        \Mockery::close();
    });
});
