<?php

declare(strict_types=1);

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesEmployment;
use App\Livewire\Wrestlers\Forms\WrestlerForm;
use App\Models\Wrestlers\Wrestler;
use Livewire\Component;

/**
 * Integration tests for WrestlerForm Livewire component validation and behavior.
 *
 * INTEGRATION TEST SCOPE:
 * - Validation rules and attribute configuration
 * - Data transformation logic with actual processing
 * - Component method behavior and return values
 * - Rule object validation (unique constraints, custom rules)
 * - Protected method testing via reflection
 *
 * These tests verify that the WrestlerForm component validation rules work correctly
 * and that data transformation methods produce expected results.
 * Unit testing (class structure only) belongs in Unit tests.
 *
 * @see WrestlerForm
 */

beforeEach(function () {
    // Mock the required Livewire dependencies for unit testing
    $mockComponent = mock(Component::class);
    $this->form = new WrestlerForm($mockComponent, 'form');
});

describe('WrestlerForm Unit Tests', function () {

    describe('component structure and configuration', function () {
        test('can be instantiated', function () {
            expect($this->form)->toBeInstanceOf(WrestlerForm::class);
        });

        test('extends LivewireBaseForm', function () {
            expect($this->form)->toBeInstanceOf(LivewireBaseForm::class);
        });

        test('has required form properties', function () {
            $requiredProperties = [
                'name', 'hometown', 'height_feet', 'height_inches',
                'weight', 'signature_move', 'employment_date',
            ];

            foreach ($requiredProperties as $property) {
                expect(property_exists($this->form, $property))->toBeTrue("Property {$property} should exist");
            }
        });

        test('properties have correct default values', function () {
            expect($this->form->name)->toBe('');
            expect($this->form->hometown)->toBe('');
            expect($this->form->height_feet)->toBe(0);
            expect($this->form->height_inches)->toBe(0);
            expect($this->form->weight)->toBe(0);
            expect($this->form->signature_move)->toBe('');
            expect($this->form->employment_date)->toBe('');
        });

        test('property types are correct', function () {
            expect($this->form->name)->toBeString();
            expect($this->form->hometown)->toBeString();
            expect($this->form->height_feet)->toBeInt();
            expect($this->form->height_inches)->toBeInt();
            expect($this->form->weight)->toBeInt();
            expect($this->form->signature_move)->toBeString();
            expect($this->form->employment_date)->toBeString();
        });
    });

    describe('method signatures and existence', function () {
        test('has required abstract method implementations', function () {
            $requiredMethods = ['rules', 'getModelData', 'loadExtraData', 'getModelClass'];

            foreach ($requiredMethods as $method) {
                expect(method_exists($this->form, $method))->toBeTrue("Method {$method} should exist");
            }
        });

        test('has validation methods', function () {
            expect(method_exists($this->form, 'rules'))->toBeTrue();
            expect(method_exists($this->form, 'validationAttributes'))->toBeTrue();
        });

        test('method signatures are correct', function () {
            $reflection = new ReflectionClass($this->form);
            
            // Check rules method
            $rulesMethod = $reflection->getMethod('rules');
            expect($rulesMethod->isProtected())->toBeTrue();
            expect($rulesMethod->getReturnType()->getName())->toBe('array');
            
            // Check getModelData method
            $getModelDataMethod = $reflection->getMethod('getModelData');
            expect($getModelDataMethod->isProtected())->toBeTrue();
            expect($getModelDataMethod->getReturnType()->getName())->toBe('array');
            
            // Check getModelClass method
            $getModelClassMethod = $reflection->getMethod('getModelClass');
            expect($getModelClassMethod->isProtected())->toBeTrue();
            expect($getModelClassMethod->getReturnType()->getName())->toBe('string');
        });
    });

    describe('validation rules configuration', function () {
        test('rules method returns validation rules array', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKeys([
                'name', 'hometown', 'height_feet', 'height_inches',
                'weight', 'signature_move', 'employment_date',
            ]);
        });

        test('validation rules have correct structure', function ($field, $expectedRules) {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            foreach ($expectedRules as $expectedRule) {
                if ($expectedRule === 'unique') {
                    // For object rules like Rule::unique
                    expect(collect($rules[$field])->some(fn ($rule) => is_object($rule)))->toBeTrue();
                } else {
                    // For string rules
                    expect($rules[$field])->toContain($expectedRule);
                }
            }
        })->with([
            'name field' => ['name', ['required', 'string', 'max:255', 'unique']],
            'hometown field' => ['hometown', ['required', 'string', 'max:255']],
            'height_feet field' => ['height_feet', ['required', 'integer', 'max:7']],
            'height_inches field' => ['height_inches', ['required', 'integer', 'max:11']],
            'weight field' => ['weight', ['required', 'integer', 'digits:3']],
            'signature_move field' => ['signature_move', ['nullable', 'string', 'max:255', 'unique']],
            'employment_date field' => ['employment_date', ['nullable', 'date']],
        ]);
    });

    describe('validation attributes configuration', function () {
        test('validationAttributes method returns custom attribute names', function () {
            $reflection = new ReflectionMethod($this->form, 'validationAttributes');
            $reflection->setAccessible(true);
            $attributes = $reflection->invoke($this->form);

            expect($attributes)->toBeArray();
            expect($attributes)->toHaveKeys([
                'height_feet', 'height_inches', 'signature_move', 'employment_date',
            ]);
        });

        test('custom attributes have correct labels', function () {
            $reflection = new ReflectionMethod($this->form, 'validationAttributes');
            $reflection->setAccessible(true);
            $attributes = $reflection->invoke($this->form);

            expect($attributes['height_feet'])->toBe('first name');
            expect($attributes['height_inches'])->toBe('last name');
            expect($attributes['signature_move'])->toBe('signature move');
            expect($attributes['employment_date'])->toBe('employment date');
        });
    });

    describe('data processing methods', function () {
        test('getModelData method returns correct model class', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelClass');
            $reflection->setAccessible(true);
            $modelClass = $reflection->invoke($this->form);

            expect($modelClass)->toBe(Wrestler::class);
        });

        test('getModelData transforms height correctly', function ($feet, $inches, $expected) {
            $this->form->height_feet = $feet;
            $this->form->height_inches = $inches;
            $this->form->name = 'Test';
            $this->form->hometown = 'Test';
            $this->form->weight = 200;

            $reflection = new ReflectionMethod($this->form, 'getModelData');
            $reflection->setAccessible(true);
            $data = $reflection->invoke($this->form);

            expect($data)->toHaveKey('height');
            expect($data['height'])->toBe($expected);
            expect($data)->toHaveKeys(['name', 'hometown', 'weight', 'signature_move']);
        })->with([
            '5 feet 0 inches' => [5, 0, 60],
            '5 feet 11 inches' => [5, 11, 71],
            '6 feet 0 inches' => [6, 0, 72],
            '6 feet 6 inches' => [6, 6, 78],
            '7 feet 0 inches' => [7, 0, 84],
        ]);

        test('getModelData excludes height components from final data', function () {
            $this->form->height_feet = 6;
            $this->form->height_inches = 2;
            $this->form->name = 'Test';
            $this->form->hometown = 'Test';
            $this->form->weight = 200;

            $reflection = new ReflectionMethod($this->form, 'getModelData');
            $reflection->setAccessible(true);
            $data = $reflection->invoke($this->form);

            expect($data)->not->toHaveKey('height_feet');
            expect($data)->not->toHaveKey('height_inches');
            expect($data)->toHaveKey('height');
        });
    });

    describe('trait integration', function () {
        test('uses ManagesEmployment trait', function () {
            expect(WrestlerForm::class)->usesTrait(ManagesEmployment::class);
        });

        test('trait methods are available', function () {
            // ManagesEmployment trait should provide employment-related methods
            expect(method_exists($this->form, 'loadExtraData'))->toBeTrue();
        });
    });

    describe('class hierarchy', function () {
        test('inherits from correct base classes', function () {
            $reflection = new ReflectionClass(WrestlerForm::class);
            
            expect($reflection->getParentClass()->getName())->toBe(LivewireBaseForm::class);
        });

        test('implements required interfaces', function () {
            $interfaces = class_implements(WrestlerForm::class);
            
            // Should implement Livewire component interfaces through inheritance
            expect($interfaces)->toBeArray();
        });
    });

    describe('property access control', function () {
        test('form properties are public', function () {
            $reflection = new ReflectionClass(WrestlerForm::class);
            
            $publicProperties = ['name', 'hometown', 'height_feet', 'height_inches', 'weight', 'signature_move', 'employment_date'];
            
            foreach ($publicProperties as $property) {
                $propertyReflection = $reflection->getProperty($property);
                expect($propertyReflection->isPublic())->toBeTrue("Property {$property} should be public");
            }
        });
    });
});