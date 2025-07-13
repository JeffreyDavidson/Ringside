<?php

declare(strict_types=1);

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Venues\Forms\VenueForm;
use App\Models\Shared\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Integration tests for VenueForm component validation and behavior.
 *
 * INTEGRATION TEST SCOPE:
 * - Address validation and data integrity
 * - Venue name uniqueness constraints
 * - State validation against database
 * - ZIP code format enforcement
 * - Data transformation and model mapping
 * - Complete address management functionality
 *
 * These tests verify that the VenueForm correctly implements
 * venue location management with comprehensive validation.
 *
 * @see VenueForm
 */
describe('VenueForm Integration Tests', function () {
    beforeEach(function () {
        $mockComponent = mock(Component::class);
        $this->form = new VenueForm($mockComponent, 'form');
    });

    describe('validation rules configuration', function () {
        test('rules method returns complete address validation structure', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKeys([
                'name', 'street_address', 'city', 'state', 'zipcode'
            ]);
        });

        test('venue name validation includes uniqueness constraint', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('string');
            expect($rules['name'])->toContain('max:255');
            
            // Should contain Rule::unique validation for venues table
            $hasUniqueRule = collect($rules['name'])->contains(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Unique;
            });
            expect($hasUniqueRule)->toBeTrue();
        });

        test('address validation enforces complete information', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Street address validation
            expect($rules['street_address'])->toContain('required');
            expect($rules['street_address'])->toContain('string');
            expect($rules['street_address'])->toContain('max:255');

            // City validation
            expect($rules['city'])->toContain('required');
            expect($rules['city'])->toContain('string');
            expect($rules['city'])->toContain('max:255');
        });

        test('state validation enforces database referential integrity', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['state'])->toContain('required');
            expect($rules['state'])->toContain('string');
            
            // Should validate against states table
            $hasExistsRule = collect($rules['state'])->contains(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Exists;
            });
            expect($hasExistsRule)->toBeTrue();
        });

        test('zipcode validation enforces US postal format', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['zipcode'])->toContain('required');
            expect($rules['zipcode'])->toContain('digits:5');
        });
    });

    describe('data transformation methods', function () {
        test('getModelClass returns correct Venue class', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelClass');
            $reflection->setAccessible(true);
            $modelClass = $reflection->invoke($this->form);

            expect($modelClass)->toBe(Venue::class);
        });

        test('getModelData transforms complete venue data correctly', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelData');
            $reflection->setAccessible(true);
            
            // Set complete test data
            $this->form->name = 'Madison Square Garden';
            $this->form->street_address = '4 Pennsylvania Plaza';
            $this->form->city = 'New York';
            $this->form->state = 'New York';
            $this->form->zipcode = '10001';
            
            $data = $reflection->invoke($this->form);
            
            expect($data)->toBeArray();
            expect($data)->toHaveKeys([
                'name', 'street_address', 'city', 'state', 'zipcode'
            ]);
            expect($data['name'])->toBe('Madison Square Garden');
            expect($data['street_address'])->toBe('4 Pennsylvania Plaza');
            expect($data['city'])->toBe('New York');
            expect($data['state'])->toBe('New York');
            expect($data['zipcode'])->toBe('10001');
        });
    });

    describe('validation attributes customization', function () {
        test('validationAttributes provides readable field names', function () {
            $reflection = new ReflectionMethod($this->form, 'validationAttributes');
            $reflection->setAccessible(true);
            $attributes = $reflection->invoke($this->form);

            expect($attributes)->toBeArray();
            expect($attributes)->toHaveKeys(['street_address', 'zipcode']);
            expect($attributes['street_address'])->toBe('street address');
            expect($attributes['zipcode'])->toBe('zip code');
        });
    });

    describe('business logic validation', function () {
        test('enforces venue name uniqueness across all venues', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Check that unique rule is configured for venues table
            $uniqueRule = collect($rules['name'])->first(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Unique;
            });

            expect($uniqueRule)->not->toBeNull();
        });

        test('validates US ZIP code format specifically', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Should enforce exactly 5 digits for US postal codes
            expect($rules['zipcode'])->toContain('digits:5');
        });

        test('validates state against existing state records', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Should validate that state exists in states table
            $existsRule = collect($rules['state'])->first(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Exists;
            });

            expect($existsRule)->not->toBeNull();
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

        test('has public form properties for venue data', function () {
            $properties = ['name', 'street_address', 'city', 'state', 'zipcode'];

            foreach ($properties as $property) {
                expect(property_exists($this->form, $property))->toBeTrue("Property {$property} should exist");
            }
        });
    });

    describe('extra data loading', function () {
        test('loadExtraData method exists but has minimal implementation', function () {
            expect(method_exists($this->form, 'loadExtraData'))->toBeTrue();
            
            $reflection = new ReflectionMethod($this->form, 'loadExtraData');
            expect($reflection->getReturnType()->getName())->toBe('void');
        });
    });
});