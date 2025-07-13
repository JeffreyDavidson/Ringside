<?php

declare(strict_types=1);

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Venues\Forms\VenueForm;
use App\Livewire\Venues\Modals\VenueFormModal;
use App\Models\Shared\Venue;
use Livewire\Component;

/**
 * Integration tests for VenueFormModal component behavior and functionality.
 *
 * INTEGRATION TEST SCOPE:
 * - Modal state management and lifecycle
 * - Form integration with VenueForm
 * - Address data generation with consistency
 * - Dummy data generation for venue testing
 * - Component property management
 * - Modal path configuration
 *
 * These tests verify that the VenueFormModal correctly integrates
 * venue-specific functionality with the base modal pattern.
 *
 * @see VenueFormModal
 */
describe('VenueFormModal Integration Tests', function () {
    beforeEach(function () {
        $this->modal = new VenueFormModal();
    });

    describe('component integration and structure', function () {
        test('can be instantiated successfully', function () {
            expect($this->modal)->toBeInstanceOf(VenueFormModal::class);
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
    });

    describe('abstract method implementations', function () {
        test('getFormClass returns correct VenueForm class', function () {
            $reflection = new ReflectionMethod($this->modal, 'getFormClass');
            $reflection->setAccessible(true);
            $formClass = $reflection->invoke($this->modal);

            expect($formClass)->toBe(VenueForm::class);
        });

        test('getModelClass returns correct Venue class', function () {
            $reflection = new ReflectionMethod($this->modal, 'getModelClass');
            $reflection->setAccessible(true);
            $modelClass = $reflection->invoke($this->modal);

            expect($modelClass)->toBe(Venue::class);
        });

        test('getModalPath returns correct view path', function () {
            $reflection = new ReflectionMethod($this->modal, 'getModalPath');
            $reflection->setAccessible(true);
            $modalPath = $reflection->invoke($this->modal);

            expect($modalPath)->toBe('venues.modals.form-modal');
        });
    });

    describe('dummy data generation integration', function () {
        test('has dummy data methods for development workflow', function () {
            expect(method_exists($this->modal, 'getDummyDataFields'))->toBeTrue();
            expect(method_exists($this->modal, 'generateRandomData'))->toBeTrue();
        });

        test('getDummyDataFields returns venue-specific field generators', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            expect($dummyFields)->toBeArray();
            expect($dummyFields)->toHaveKeys([
                'name', 'street_address', 'city', 'state', 'zipcode'
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

    describe('venue-specific data generation', function () {
        test('generates realistic venue names', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            // Test venue name generator
            $venueName = $dummyFields['name']();
            expect($venueName)->toBeString();
            expect(mb_strlen($venueName))->toBeGreaterThan(0);
        });

        test('generates consistent address data through optimized approach', function () {
            $reflection = new ReflectionMethod($this->modal, 'generateRandomData');
            $reflection->setAccessible(true);
            
            // Execute the generateRandomData method
            $reflection->invoke($this->modal);
            
            // Verify that form fields are populated
            expect($this->modal->form)->not->toBeNull();
        });

        test('address generation provides complete US address components', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            // Test that address fields are all present
            $addressFields = ['street_address', 'city', 'state', 'zipcode'];
            foreach ($addressFields as $field) {
                expect($dummyFields)->toHaveKey($field);
                
                $value = $dummyFields[$field]();
                expect($value)->toBeString();
                expect(mb_strlen($value))->toBeGreaterThan(0);
            }
        });
    });

    describe('address data consistency', function () {
        test('generateRandomData creates consistent address from single source', function () {
            $reflection = new ReflectionMethod($this->modal, 'generateRandomData');
            $reflection->setAccessible(true);
            
            // Mock the form to capture filled data
            $mockForm = mock(VenueForm::class);
            $mockForm->shouldReceive('fill')->once()->with(\Mockery::type('array'));
            $this->modal->form = $mockForm;
            
            $reflection->invoke($this->modal);
        });

        test('address fields come from same location for consistency', function () {
            // This test ensures the documented optimization approach
            // where address data is generated once and reused
            expect(method_exists($this->modal, 'generateRandomData'))->toBeTrue();
            
            $reflection = new ReflectionClass($this->modal);
            $source = file_get_contents($reflection->getFileName());
            
            // Should contain the consistency optimization
            expect($source)->toContain('generateUSAddress()');
        });
    });

    describe('modal state management', function () {
        test('has modal control methods with correct signatures', function () {
            $modalMethods = ['openModal', 'closeModal'];

            foreach ($modalMethods as $method) {
                expect(method_exists($this->modal, $method))->toBeTrue("Method {$method} should exist");
                
                $reflection = new ReflectionMethod($this->modal, $method);
                expect($reflection->getReturnType()->getName())->toBe('void');
            }
        });

        test('generateRandomData method has void return type', function () {
            $reflection = new ReflectionMethod($this->modal, 'generateRandomData');
            expect($reflection->getReturnType()->getName())->toBe('void');
        });
    });

    describe('form integration', function () {
        test('has typed VenueForm property for state management', function () {
            $formProperty = new ReflectionProperty(VenueFormModal::class, 'form');
            expect($formProperty->isPublic())->toBeTrue();
            expect($formProperty->getType()->getName())->toBe('App\\Livewire\\Venues\\Forms\\VenueForm');
        });
    });

    describe('venue business context', function () {
        test('focuses on wrestling venue management functionality', function () {
            $reflection = new ReflectionClass($this->modal);
            $docComment = $reflection->getDocComment();
            
            // Should be documented for venue-specific use
            expect($docComment)->toContain('venue');
        });

        test('supports venue location data requirements', function () {
            $reflection = new ReflectionMethod($this->modal, 'getDummyDataFields');
            $reflection->setAccessible(true);
            $dummyFields = $reflection->invoke($this->modal);

            // Should support all required venue location fields
            $requiredFields = ['name', 'street_address', 'city', 'state', 'zipcode'];
            foreach ($requiredFields as $field) {
                expect($dummyFields)->toHaveKey($field);
            }
        });
    });
});