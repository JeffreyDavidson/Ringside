<?php

declare(strict_types=1);

use App\Livewire\Base\BaseForm;
use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;
use App\ValueObjects\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use JMac\Testing\Double;
use Livewire\Component;

final class VenueFormTestProxy extends CreateEditForm
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rulesForTesting(): array
    {
        return $this->rules();
    }

    /**
     * @return array<string, mixed>
     */
    public function modelDataForTesting(): array
    {
        return $this->getModelData();
    }

    /**
     * @return class-string<Venue>
     */
    public function modelClassForTesting(): string
    {
        return $this->getModelClass();
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributesForTesting(): array
    {
        return $this->validationAttributes();
    }
}

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
        $mockComponent = Double::for(Component::class);
        $this->form = new VenueFormTestProxy($mockComponent, 'form');
    });

    describe('validation rules configuration', function () {
        test('rules method returns complete address validation structure', function () {
            $rules = $this->form->rulesForTesting();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKeys([
                'name', 'street_address', 'city', 'state', 'zipcode',
            ]);
        });

        test('venue name validation includes uniqueness constraint', function () {
            $rules = $this->form->rulesForTesting();

            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('string');
            expect($rules['name'])->toContain('max:255');

            // Should contain Rule::unique validation for venues table
            $nameRules = $rules['name'];

            $hasUniqueRule = collect($nameRules)->contains(function ($rule) {
                return $rule instanceof Unique;
            });
            expect($hasUniqueRule)->toBeTrue();
        });

        test('address validation enforces complete information', function () {
            $rules = $this->form->rulesForTesting();

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
            $rules = $this->form->rulesForTesting();

            expect($rules['state'])->toContain('required');
            expect($rules['state'])->toContain('string');

            // Should validate against states table
            $stateRules = $rules['state'];

            $hasExistsRule = collect($stateRules)->contains(function ($rule) {
                return $rule instanceof Exists;
            });
            expect($hasExistsRule)->toBeTrue();
        });

        test('zipcode validation enforces US postal format', function () {
            $rules = $this->form->rulesForTesting();

            expect($rules['zipcode'])->toContain('required');
            expect($rules['zipcode'])->toContain('digits:5');
        });
    });

    describe('data transformation methods', function () {
        test('getModelClass returns correct Venue class', function () {
            expect($this->form->modelClassForTesting())->toBe(Venue::class);
        });

        test('getModelData transforms complete venue data correctly', function () {
            $this->form->name = 'Madison Square Garden';
            $this->form->street_address = '4 Pennsylvania Plaza';
            $this->form->city = 'New York';
            $this->form->state = 'New York';
            $this->form->zipcode = '10001';

            $data = $this->form->modelDataForTesting();

            expect($data)->toBeArray();
            expect($data)->toHaveKeys(['name', 'address']);
            expect($data['name'])->toBe('Madison Square Garden')
                ->and($data['address'])->toEqual(
                    new Address('4 Pennsylvania Plaza', 'New York', 'New York', '10001'),
                );
        });
    });

    describe('validation attributes customization', function () {
        test('validationAttributes provides readable field names', function () {
            $attributes = $this->form->validationAttributesForTesting();

            expect($attributes)->toBeArray();
            expect($attributes)->toHaveKeys(['street_address', 'zipcode']);
            expect($attributes['street_address'])->toBe('street address');
            expect($attributes['zipcode'])->toBe('zip code');
        });
    });

    describe('business logic validation', function () {
        test('enforces venue name uniqueness across all venues', function () {
            $rules = $this->form->rulesForTesting();

            // Check that unique rule is configured for venues table
            $nameRules = $rules['name'];

            $uniqueRule = collect($nameRules)->first(function ($rule) {
                return $rule instanceof Unique;
            });

            expect($uniqueRule)->not()->toBeNull();
        });

        test('validates US ZIP code format specifically', function () {
            $rules = $this->form->rulesForTesting();

            expect($rules['zipcode'])->toContain('digits:5');
        });

        test('validates state against existing state records', function () {
            $rules = $this->form->rulesForTesting();

            // Should validate that state exists in states table
            $stateRules = $rules['state'];

            $existsRule = collect($stateRules)->first(function ($rule) {
                return $rule instanceof Exists;
            });

            expect($existsRule)->not()->toBeNull();
        });
    });

    describe('form inheritance and structure', function () {
        test('extends BaseForm correctly', function () {
            expect($this->form)->toBeInstanceOf(BaseForm::class);
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
        test('loadExtraData has a void return type', function () {
            $reflection = new ReflectionMethod($this->form, 'loadExtraData');
            expect(reflectionReturnTypeName($reflection))->toBe('void');
        });
    });
});
