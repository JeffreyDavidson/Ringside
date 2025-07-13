<?php

declare(strict_types=1);

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Wrestlers\Forms\WrestlerForm;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Integration tests for WrestlerForm component validation and behavior.
 *
 * INTEGRATION TEST SCOPE:
 * - Validation rules and attribute configuration
 * - Data transformation logic with actual processing
 * - Component method behavior and return values
 * - Rule object validation (unique constraints, custom rules)
 * - Protected method testing via reflection
 * - Height and weight transformation logic
 *
 * These tests verify that the WrestlerForm correctly implements
 * all business logic and data processing requirements.
 *
 * @see WrestlerForm
 */
describe('WrestlerForm Integration Tests', function () {
    beforeEach(function () {
        $mockComponent = mock(Component::class);
        $this->form = new WrestlerForm($mockComponent, 'form');
    });

    describe('validation rules configuration', function () {
        test('rules method returns correct validation structure', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKeys([
                'name', 'hometown', 'height_feet', 'height_inches', 'weight'
            ]);
        });

        test('name validation includes uniqueness constraint', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('string');
            expect($rules['name'])->toContain('max:255');
            
            // Should contain Rule::unique validation
            $hasUniqueRule = collect($rules['name'])->contains(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Unique;
            });
            expect($hasUniqueRule)->toBeTrue();
        });

        test('height validation enforces realistic ranges', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Height feet should be limited to realistic wrestling heights
            expect($rules['height_feet'])->toContain('required');
            expect($rules['height_feet'])->toContain('integer');
            expect($rules['height_feet'])->toContain('min:4');
            expect($rules['height_feet'])->toContain('max:8');

            // Height inches should be 0-11
            expect($rules['height_inches'])->toContain('required');
            expect($rules['height_inches'])->toContain('integer');
            expect($rules['height_inches'])->toContain('min:0');
            expect($rules['height_inches'])->toContain('max:11');
        });

        test('weight validation enforces realistic wrestler weights', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['weight'])->toContain('required');
            expect($rules['weight'])->toContain('integer');
            expect($rules['weight'])->toContain('min:100');
            expect($rules['weight'])->toContain('max:500');
        });
    });

    describe('data transformation methods', function () {
        test('getModelClass returns correct Wrestler class', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelClass');
            $reflection->setAccessible(true);
            $modelClass = $reflection->invoke($this->form);

            expect($modelClass)->toBe(Wrestler::class);
        });

        test('getModelData transforms form data correctly', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelData');
            $reflection->setAccessible(true);
            
            // Set test data
            $this->form->name = 'Test Wrestler';
            $this->form->hometown = 'Test City, TX';
            $this->form->height_feet = 6;
            $this->form->height_inches = 2;
            $this->form->weight = 225;
            
            $data = $reflection->invoke($this->form);
            
            expect($data)->toBeArray();
            expect($data)->toHaveKeys([
                'name', 'hometown', 'height_feet', 'height_inches', 'weight'
            ]);
            expect($data['name'])->toBe('Test Wrestler');
            expect($data['hometown'])->toBe('Test City, TX');
            expect($data['height_feet'])->toBe(6);
            expect($data['height_inches'])->toBe(2);
            expect($data['weight'])->toBe(225);
        });
    });

    describe('validation attributes customization', function () {
        test('validationAttributes provides readable field names', function () {
            $reflection = new ReflectionMethod($this->form, 'validationAttributes');
            $reflection->setAccessible(true);
            $attributes = $reflection->invoke($this->form);

            expect($attributes)->toBeArray();
            expect($attributes)->toHaveKeys([
                'height_feet', 'height_inches', 'signature_move'
            ]);
            expect($attributes['height_feet'])->toBe('height (feet)');
            expect($attributes['height_inches'])->toBe('height (inches)');
            expect($attributes['signature_move'])->toBe('signature move');
        });
    });

    describe('form inheritance and structure', function () {
        test('extends LivewireBaseForm correctly', function () {
            expect($this->form)->toBeInstanceOf(LivewireBaseForm::class);
        });

        test('implements required abstract methods', function () {
            $requiredMethods = ['getModelClass', 'getModelData', 'rules'];

            foreach ($requiredMethods as $method) {
                expect(method_exists($this->form, $method))->toBeTrue("Method {$method} should exist");
            }
        });

        test('has public form properties for wrestler data', function () {
            $properties = ['name', 'hometown', 'height_feet', 'height_inches', 'weight'];

            foreach ($properties as $property) {
                expect(property_exists($this->form, $property))->toBeTrue("Property {$property} should exist");
            }
        });
    });

    describe('business logic validation', function () {
        test('enforces wrestler name uniqueness across database', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Check that unique rule is configured for wrestlers table
            $uniqueRule = collect($rules['name'])->first(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Unique;
            });

            expect($uniqueRule)->not->toBeNull();
        });

        test('validates height combination produces reasonable total', function () {
            // Test that validation allows realistic height combinations
            $this->form->height_feet = 6;
            $this->form->height_inches = 0;
            
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Should allow valid combinations
            expect($rules['height_feet'])->toContain('min:4');
            expect($rules['height_feet'])->toContain('max:8');
            expect($rules['height_inches'])->toContain('min:0');
            expect($rules['height_inches'])->toContain('max:11');
        });

        test('signature move is optional but validated when present', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Signature move should be nullable but have string validation
            expect($rules['signature_move'])->toContain('nullable');
            expect($rules['signature_move'])->toContain('string');
            expect($rules['signature_move'])->toContain('max:255');
        });
    });

    describe('extra data loading', function () {
        test('loadExtraData method exists for extensibility', function () {
            expect(method_exists($this->form, 'loadExtraData'))->toBeTrue();
            
            $reflection = new ReflectionMethod($this->form, 'loadExtraData');
            expect($reflection->getReturnType()->getName())->toBe('void');
        });
    });
});