<?php

declare(strict_types=1);

use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit tests for CanChangeEmploymentDate validation rule.
 *
 * UNIT TEST SCOPE:
 * - Method existence checking logic (method_exists conditions)
 * - Employment status validation with mocked dependencies
 * - Date parsing and comparison logic
 * - Model name resolution strategies (name property, getDisplayName, class_basename)
 * - Error message formatting with dynamic model names
 * - Edge cases (null models, missing methods, various date formats)
 *
 * These tests verify the CanChangeEmploymentDate rule logic independently
 * of models, database, or Laravel's validation framework.
 *
 * @see \App\Rules\Shared\CanChangeEmploymentDate
 */
describe('CanChangeEmploymentDate Validation Rule Unit Tests', function () {
    describe('model validation with employment methods', function () {
        test('validation passes when model is not employed', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isEmployed')->andReturn(false);

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation fails when model is employed and employedOn returns false', function () {
            // Arrange
            $model = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute(
                    $key) { return $key === 'name' ? 'Test Wrestler' : null; }
            };

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('started_at', now()->addMonth(), $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toBe('The employment date cannot be changed while Test Wrestler is currently employed.');
        });

        test('validation passes when model is employed and employedOn returns true', function () {
            // Arrange
            $model = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return true; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Wrestler' : null; }
            };

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', now()->addMonth(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when model is employed but lacks employedOn method', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isEmployed')->andReturn(true);
            $model->shouldReceive('getAttribute')->with('name')->andReturn('Test Model');

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('method existence checking logic', function () {
        test('validation passes when model lacks isEmployed method', function () {
            // Arrange
            $model = \Mockery::mock(Model::class);
            // Note: No isEmployed method mocked - simulates method_exists() returning false

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when no model provided', function () {
            // Arrange
            $rule = new CanChangeEmploymentDate(null);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('model name resolution strategies', function () {
        test('uses model name property when available', function () {
            // Arrange
            $model = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Custom Model Name' : null; }
            };

            $rule = new CanChangeEmploymentDate($model);
            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('started_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failMessage)->toContain('Custom Model Name');
        });

        test('uses class basename when name property missing', function () {
            // Arrange
            $model = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute($key) { return null; }
            };

            $rule = new CanChangeEmploymentDate($model);
            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('started_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failMessage)->toMatch('/The employment date cannot be changed while .+ is currently employed\./');
        });
    });

    describe('date parsing and handling', function () {
        test('handles Carbon date instances', function () {
            // Arrange
            $model = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Model' : null; }
            };

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', now()->addWeek(), $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
        });

        test('handles string date values', function () {
            // Arrange
            $model = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Model' : null; }
            };

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', '2024-12-25', $failCallback);

            // Assert
            expect($failCalled)->toBeTrue();
        });

        test('correctly passes parsed date to employedOn method', function () {
            // Arrange
            $targetDate = now()->addWeek();
            $model = \Mockery::mock(Model::class);
            $model->shouldReceive('isEmployed')->andReturn(true);
            $model->shouldReceive('employedOn')
                  ->with(\Mockery::on(function ($date) use ($targetDate) {
                      return $date instanceof \Illuminate\Support\Carbon &&
                             $date->equalTo($targetDate);
                  }))
                  ->andReturn(true);
            $model->shouldReceive('getAttribute')->with('name')->andReturn('Test Model');

            $rule = new CanChangeEmploymentDate($model);
            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('started_at', $targetDate, $failCallback);

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('interface compliance', function () {
        test('rule implements ValidationRule interface', function () {
            // Arrange
            $rule = new CanChangeEmploymentDate(null);

            // Assert
            expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
        });

        test('validate method signature matches interface', function () {
            // Arrange
            $rule = new CanChangeEmploymentDate(null);
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
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Model One' : null; }
            };
            $model2 = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Model Two' : null; }
            };
            $messages = [];
            $failCallback = function (string $message) use (&$messages) {
                $messages[] = $message;
            };
            // Act
            (new CanChangeEmploymentDate($model1))->validate('started_at', now(), $failCallback);
            (new CanChangeEmploymentDate($model2))->validate('started_at', now(), $failCallback);
            // Assert
            expect($messages)->toHaveCount(2);
            expect($messages[0])->toBe('The employment date cannot be changed while Model One is currently employed.');
            expect($messages[1])->toBe('The employment date cannot be changed while Model Two is currently employed.');
        });
        test('attribute name does not affect validation logic', function () {
            // Arrange
            $model = new class extends Model {
                public function isEmployed() { return true; }
                public function employedOn($date = null) { return false; }
                public function getAttribute($key) { return $key === 'name' ? 'Test Model' : null; }
            };
            $rule = new CanChangeEmploymentDate($model);
            $failCallCount = 0;
            $failCallback = function () use (&$failCallCount) {
                $failCallCount++;
            };
            // Act
            $rule->validate('started_at', now(), $failCallback);
            $rule->validate('employment_date', now(), $failCallback);
            $rule->validate('hire_date', now(), $failCallback);
            // Assert
            expect($failCallCount)->toBe(3);
        });
    });

    afterEach(function () {
        \Mockery::close();
    });
});
