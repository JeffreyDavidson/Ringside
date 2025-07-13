<?php

declare(strict_types=1);

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\BaseModal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use LivewireUI\Modal\ModalComponent;

/**
 * Unit tests for BaseModal abstract class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Generic type annotations
 * - Property structure and visibility
 * - Method signatures and return types
 * - Abstract class requirements
 *
 * @see BaseModal
 * @see \Tests\Integration\Livewire\Concerns\BaseModalTest
 */
describe('BaseModal Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends ModalComponent', function () {
            expect(BaseModal::class)->toExtend(ModalComponent::class);
        });

        test('is abstract class', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

        test('has generic type annotations', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            $docComment = $reflection->getDocComment();
            
            expect($docComment)->toContain('@template TModelForm of LivewireBaseForm');
            expect($docComment)->toContain('@template TModelType of Model');
        });
    });

    describe('property structure', function () {
        test('has model property', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasProperty('model'))->toBeTrue();
            
            $property = $reflection->getProperty('model');
            expect($property->isProtected())->toBeTrue();
            expect($property->getType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Model');
            expect($property->getType()->allowsNull())->toBeTrue();
        });

        test('has modelForm property', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasProperty('modelForm'))->toBeTrue();
            
            $property = $reflection->getProperty('modelForm');
            expect($property->isProtected())->toBeTrue();
            expect($property->getDocComment())->toContain('@var TModelForm');
        });

        test('has modelType property', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasProperty('modelType'))->toBeTrue();
            
            $property = $reflection->getProperty('modelType');
            expect($property->isProtected())->toBeTrue();
            expect($property->getType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Model');
            expect($property->getDocComment())->toContain('@var TModelType');
        });

        test('has string configuration properties', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasProperty('modalFormPath'))->toBeTrue();
            expect($reflection->hasProperty('modelTitleField'))->toBeTrue();
            expect($reflection->hasProperty('titleField'))->toBeTrue();
            
            $modalFormPath = $reflection->getProperty('modalFormPath');
            expect($modalFormPath->isProtected())->toBeTrue();
            expect($modalFormPath->getType()->getName())->toBe('string');
            
            $modelTitleField = $reflection->getProperty('modelTitleField');
            expect($modelTitleField->isProtected())->toBeTrue();
            expect($modelTitleField->getType()->getName())->toBe('string');
            
            $titleField = $reflection->getProperty('titleField');
            expect($titleField->isProtected())->toBeTrue();
            expect($titleField->getType()->getName())->toBe('string');
        });
    });

    describe('method signatures', function () {
        test('has mount method', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasMethod('mount'))->toBeTrue();
            
            $method = $reflection->getMethod('mount');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
            expect($method->getNumberOfParameters())->toBe(1);
            
            $parameter = $method->getParameters()[0];
            expect($parameter->getName())->toBe('modelId');
            expect($parameter->getType()->getName())->toBe('mixed');
            expect($parameter->isOptional())->toBeTrue();
            expect($parameter->getDefaultValue())->toBeNull();
        });

        test('has getModalTitle method', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasMethod('getModalTitle'))->toBeTrue();
            
            $method = $reflection->getMethod('getModalTitle');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('string');
            expect($method->getNumberOfParameters())->toBe(0);
        });

        test('has clear method', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasMethod('clear'))->toBeTrue();
            
            $method = $reflection->getMethod('clear');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
            expect($method->getNumberOfParameters())->toBe(0);
        });

        test('has save method', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasMethod('save'))->toBeTrue();
            
            $method = $reflection->getMethod('save');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
            expect($method->getNumberOfParameters())->toBe(0);
        });

        test('has render method', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            expect($reflection->hasMethod('render'))->toBeTrue();
            
            $method = $reflection->getMethod('render');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('Illuminate\\View\\View');
            expect($method->getNumberOfParameters())->toBe(0);
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Concerns');
        });

        test('follows base class naming convention', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            expect($reflection->getShortName())->toBe('BaseModal');
        });
    });

    describe('dependency imports', function () {
        test('imports required dependencies', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use App\\Livewire\\Base\\LivewireBaseForm;');
            expect($source)->toContain('use Illuminate\\Database\\Eloquent\\Model;');
            expect($source)->toContain('use Illuminate\\View\\View;');
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
            expect($reflection->hasMethod('save'))->toBeTrue();
            expect($reflection->hasMethod('render'))->toBeTrue();
        });
    });

    describe('generic type safety', function () {
        test('uses generic type constraints', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            $docComment = $reflection->getDocComment();
            
            expect($docComment)->toContain('TModelForm of LivewireBaseForm');
            expect($docComment)->toContain('TModelType of Model');
        });

        test('property annotations use generic types', function () {
            $reflection = new ReflectionClass(BaseModal::class);
            
            $modelFormProperty = $reflection->getProperty('modelForm');
            expect($modelFormProperty->getDocComment())->toContain('@var TModelForm');
            
            $modelTypeProperty = $reflection->getProperty('modelType');
            expect($modelTypeProperty->getDocComment())->toContain('@var TModelType');
        });
    });
});