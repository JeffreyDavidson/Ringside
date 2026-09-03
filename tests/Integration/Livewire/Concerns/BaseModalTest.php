<?php

declare(strict_types=1);

use App\Livewire\Base\BaseModal;
use LivewireUI\Modal\ModalComponent;
use Tests\Integration\Livewire\Concerns\BaseModalTest;

/**
 * Integration tests for BaseModal abstract class structure.
 *
 * INTEGRATION TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Generic type annotations
 * - Property structure and visibility
 * - Method signatures and return types
 * - Abstract class requirements
 *
 * @see BaseModal
 * @see BaseModalTest
 */
describe('BaseModal Integration Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends ModalComponent', function () {
            expect(BaseModal::class)->toExtend(ModalComponent::class);
        });

        test('is abstract class', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

    });

    describe('property structure', function () {
        test('has model property', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasProperty('model'))->toBeTrue();

            $property = $reflection->getProperty('model');
            expect($property->isProtected())->toBeTrue();
            expect(reflectionTypeName($property))->toBe('Illuminate\\Database\\Eloquent\\Model');
            expect(requiredReflectionType($property->getType())->allowsNull())->toBeTrue();
        });

        test('has modelForm property', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasProperty('modelForm'))->toBeTrue();

            $property = $reflection->getProperty('modelForm');
            expect($property->isProtected())->toBeTrue();
        });

        test('has model class configuration', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasProperty('modelClass'))->toBeTrue();

            $property = $reflection->getProperty('modelClass');
            expect($property->isProtected())->toBeTrue();
            expect(reflectionTypeName($property))->toBe('string');
        });

        test('has model title configuration', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasProperty('modelTitleField'))->toBeTrue();

            $modelTitleField = $reflection->getProperty('modelTitleField');
            expect($modelTitleField->isProtected())->toBeTrue();
            expect(reflectionTypeName($modelTitleField))->toBe('string');

        });
    });

    describe('method signatures', function () {
        test('has mount method', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasMethod('mount'))->toBeTrue();

            $method = $reflection->getMethod('mount');
            expect($method->isPublic())->toBeTrue();
            expect(reflectionReturnTypeName($method))->toBe('void');
            expect($method->getNumberOfParameters())->toBe(1);

            $parameter = $method->getParameters()[0];
            expect($parameter->getName())->toBe('modelId');

            expect((string) $parameter->getType())->toBe('string|int|null');
            expect($parameter->isOptional())->toBeTrue();
            expect($parameter->getDefaultValue())->toBeNull();
        });

        test('has getModalTitle method', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasMethod('getModalTitle'))->toBeTrue();

            $method = $reflection->getMethod('getModalTitle');
            expect($method->isPublic())->toBeTrue();
            expect(reflectionReturnTypeName($method))->toBe('string');
            expect($method->getNumberOfParameters())->toBe(0);
        });

        test('has clear method', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasMethod('clear'))->toBeTrue();

            $method = $reflection->getMethod('clear');
            expect($method->isPublic())->toBeTrue();
            expect(reflectionReturnTypeName($method))->toBe('void');
            expect($method->getNumberOfParameters())->toBe(0);
        });

        test('leaves the submission workflow to form modals', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            expect($reflection->hasMethod('save'))->toBeFalse();
        });

    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Base');
        });

        test('follows base class naming convention', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            expect($reflection->getShortName())->toBe('BaseModal');
        });
    });

    describe('dependency imports', function () {
        test('imports required dependencies', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            $source = reflectionSource($reflection);

            // Check for actual imports in BaseModal
            expect($source)->toContain('use Illuminate\\Database\\Eloquent\\Model;');
            expect($source)->toContain('use LivewireUI\\Modal\\ModalComponent;');
        });
    });

    describe('template method pattern', function () {
        test('follows template method pattern', function () {
            $reflection = new ReflectionClass(BaseModal::class);

            // Should be abstract (template)
            expect($reflection->isAbstract())->toBeTrue();

            // Should have template methods
            expect($reflection->hasMethod('mount'))->toBeTrue();
        });
    });

});
