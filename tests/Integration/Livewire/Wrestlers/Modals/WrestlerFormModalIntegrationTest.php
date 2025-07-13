<?php

declare(strict_types=1);

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Wrestlers\Forms\WrestlerForm;
use App\Livewire\Wrestlers\Modals\WrestlerFormModal;
use App\Models\Wrestlers\Wrestler;
use Livewire\Component;

/**
 * Integration tests for WrestlerFormModal component behavior and functionality.
 *
 * INTEGRATION TEST SCOPE:
 * - Component instantiation and inheritance behavior
 * - Modal state management and lifecycle
 * - Form integration and configuration
 * - Dummy data generation with realistic business values
 * - Method invocation and business logic validation
 * - Component property management and types
 *
 * These tests verify that the WrestlerFormModal correctly integrates its
 * various components and provides the expected business functionality.
 *
 * @see WrestlerFormModal
 */
describe('WrestlerFormModal Integration Tests', function () {
    beforeEach(function () {
        $this->modal = new WrestlerFormModal();
    });

    describe('component integration and structure', function () {
        test('can be instantiated successfully', function () {
            expect($this->modal)->toBeInstanceOf(WrestlerFormModal::class);
        });

        test('extends BaseFormModal with proper inheritance', function () {
            expect($this->modal)->toBeInstanceOf(BaseFormModal::class);
            expect($this->modal)->toBeInstanceOf(Component::class);
        });

        test('has required properties for modal functionality', function () {
            $requiredProperties = ['form', 'isModalOpen', 'modalFormPath'];

            foreach ($requiredProperties as $property) {
                expect(property_exists($this->modal, $property))->toBeTrue("Property {$property} should exist");
            }
        });

        test('has required methods for complete functionality', function () {
            $requiredMethods = [
                'mount', 'openModal', 'closeModal', 'render',
                'getFormClass', 'getModelClass', 'getModalPath',
            ];

            foreach ($requiredMethods as $method) {
                expect(method_exists($this->modal, $method))->toBeTrue("Method {$method} should exist");
            }
        });
    });

    describe('abstract method implementations', function () {
        test('getFormClass returns correct WrestlerForm class', function () {
            $reflection = new ReflectionMethod($this->modal, 'getFormClass');
            $reflection->setAccessible(true);
            $formClass = $reflection->invoke($this->modal);

            expect($formClass)->toBe(WrestlerForm::class);
        });

        test('getModelClass returns correct Wrestler class', function () {
            $reflection = new ReflectionMethod($this->modal, 'getModelClass');
            $reflection->setAccessible(true);
            $modelClass = $reflection->invoke($this->modal);

            expect($modelClass)->toBe(Wrestler::class);
        });

        test('getModalPath returns correct view path', function () {
            $reflection = new ReflectionMethod($this->modal, 'getModalPath');
            $reflection->setAccessible(true);
            $modalPath = $reflection->invoke($this->modal);

            expect($modalPath)->toBe('wrestlers.modals.form-modal');
        });
    });

    describe('method parameter validation', function () {
        test('mount method accepts optional model id parameter', function () {
            $reflection = new ReflectionMethod($this->modal, 'mount');
            $parameters = $reflection->getParameters();

            expect($parameters)->toHaveCount(1);
            expect($parameters[0]->getName())->toBe('modelId');
            expect($parameters[0]->allowsNull())->toBeTrue();
        });

        test('openModal method accepts optional parameter', function () {
            $reflection = new ReflectionMethod($this->modal, 'openModal');
            $parameters = $reflection->getParameters();

            expect($parameters)->toHaveCount(1);
            expect($parameters[0]->getName())->toBe('modelId');
            expect($parameters[0]->allowsNull())->toBeTrue();
        });

        test('modal control methods have void return types', function () {
            $modalMethods = ['openModal', 'closeModal', 'fillDummyFields'];

            foreach ($modalMethods as $method) {
                $reflection = new ReflectionMethod($this->modal, $method);
                expect($reflection->getReturnType()->getName())->toBe('void');
            }
        });
    });

    describe('dummy data generation integration', function () {
        test('has dummy data methods for development workflow', function () {
            expect(method_exists($this->modal, 'getDummyDataFields'))->toBeTrue();
            expect(method_exists($this->modal, 'fillDummyFields'))->toBeTrue();
        });

        test('getDummyDataFields returns array with required wrestling fields', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            expect($dummyFields)->toBeArray();
            expect($dummyFields)->toHaveKeys([
                'name', 'hometown', 'height_feet', 'height_inches',
                'weight', 'signature_move', 'employment_date',
            ]);
        });

        test('dummy data fields have callable generators', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            foreach ($dummyFields as $field => $generator) {
                expect(is_callable($generator))->toBeTrue("Field {$field} generator should be callable");
            }
        });
    });

    describe('realistic business data generation', function () {
        test('generates realistic wrestling height values', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            // Test height_feet generator produces realistic values
            $heightFeet = $dummyFields['height_feet']();
            expect($heightFeet)->toBeGreaterThanOrEqual(5);
            expect($heightFeet)->toBeLessThanOrEqual(7);

            // Test height_inches generator produces valid range
            $heightInches = $dummyFields['height_inches']();
            expect($heightInches)->toBeGreaterThanOrEqual(0);
            expect($heightInches)->toBeLessThanOrEqual(11);
        });

        test('generates realistic wrestling weight values', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            // Test weight generator produces realistic wrestler weights
            $weight = $dummyFields['weight']();
            expect($weight)->toBeGreaterThanOrEqual(150);
            expect($weight)->toBeLessThanOrEqual(350);
        });

        test('generates realistic wrestling names and locations', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            // Test name generator returns non-empty string
            $name = $dummyFields['name']();
            expect($name)->toBeString();
            expect(mb_strlen($name))->toBeGreaterThan(0);

            // Test hometown includes state abbreviation format
            $hometown = $dummyFields['hometown']();
            expect($hometown)->toBeString();
            expect($hometown)->toContain(', '); // City, ST format
        });
    });

    describe('component property management', function () {
        test('has correct property types for form integration', function () {
            $formProperty = new ReflectionProperty(WrestlerFormModal::class, 'form');
            expect($formProperty->isPublic())->toBeTrue();

            $modalOpenProperty = new ReflectionProperty(WrestlerFormModal::class, 'isModalOpen');
            expect($modalOpenProperty->isPublic())->toBeTrue();

            $modalPathProperty = new ReflectionProperty(WrestlerFormModal::class, 'modalFormPath');
            expect($modalPathProperty->isProtected())->toBeTrue();
        });
    });

    describe('trait integration', function () {
        test('uses GeneratesDummyData trait for functionality', function () {
            $traits = class_uses_recursive(WrestlerFormModal::class);

            expect($traits)->toContain('App\Livewire\Concerns\GeneratesDummyData');
        });
    });
});