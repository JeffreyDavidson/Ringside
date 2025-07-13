<?php

declare(strict_types=1);

use App\Livewire\Base\LivewireBaseForm;
use Livewire\Form;

/**
 * Unit tests for LivewireBaseForm abstract class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Abstract method existence and signatures
 * - Property visibility and types
 * - Method signatures and return types
 * - Generic type annotations
 *
 * @see LivewireBaseForm
 * @see \Tests\Integration\Livewire\Base\LivewireBaseFormTest
 */
describe('LivewireBaseForm Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends Livewire Form class', function () {
            expect(LivewireBaseForm::class)->toExtend(Form::class);
        });

        test('is abstract class', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            expect($reflection->isAbstract())->toBeTrue();
        });
    });

    describe('abstract methods', function () {
        test('has required abstract methods', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            
            $abstractMethods = $reflection->getMethods(ReflectionMethod::IS_ABSTRACT);
            $abstractMethodNames = array_map(fn($method) => $method->getName(), $abstractMethods);
            
            expect($abstractMethodNames)->toContain('rules');
            expect($abstractMethodNames)->toContain('getModelData');
        });
    });

    describe('method signatures', function () {
        test('abstract methods have correct signatures', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            
            // rules method
            $rules = $reflection->getMethod('rules');
            expect($rules->isAbstract())->toBeTrue();
            expect($rules->isProtected())->toBeTrue();
            expect($rules->getReturnType()->getName())->toBe('array');
            
            // getModelData method
            $getModelData = $reflection->getMethod('getModelData');
            expect($getModelData->isAbstract())->toBeTrue();
            expect($getModelData->isProtected())->toBeTrue();
            expect($getModelData->getReturnType()->getName())->toBe('array');
        });
    });

    describe('concrete methods', function () {
        test('has concrete methods for common functionality', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            
            $concreteMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
            $concreteMethodNames = array_map(fn($method) => $method->getName(), $concreteMethods);
            
            expect($concreteMethodNames)->toContain('fill');
            expect($concreteMethodNames)->toContain('submit');
            expect($concreteMethodNames)->toContain('validationAttributes');
        });
    });

    describe('property structure', function () {
        test('has formModel property', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            
            expect($reflection->hasProperty('formModel'))->toBeTrue();
            
            $formModelProperty = $reflection->getProperty('formModel');
            expect($formModelProperty->isProtected())->toBeTrue();
            expect($formModelProperty->hasType())->toBeTrue();
            expect($formModelProperty->getType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Model');
            expect($formModelProperty->getType()->allowsNull())->toBeTrue();
        });
    });

    describe('template method pattern', function () {
        test('implements template method pattern', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            
            // Should be abstract (template)
            expect($reflection->isAbstract())->toBeTrue();
            
            // Should have abstract methods for child configuration
            $abstractMethods = $reflection->getMethods(ReflectionMethod::IS_ABSTRACT);
            expect($abstractMethods)->not->toBeEmpty();
            
            // Should have concrete methods for common workflow
            $concreteMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
            $concreteMethodNames = array_map(fn($method) => $method->getName(), $concreteMethods);
            expect($concreteMethodNames)->toContain('submit');
            expect($concreteMethodNames)->toContain('fill');
        });
    });

    describe('method visibility', function () {
        test('protected methods are properly encapsulated', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            
            // Abstract methods should be protected
            $rules = $reflection->getMethod('rules');
            expect($rules->isProtected())->toBeTrue();
            
            $getModelData = $reflection->getMethod('getModelData');
            expect($getModelData->isProtected())->toBeTrue();
        });
    });

    describe('class constants', function () {
        test('has no class-specific constants', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            $constants = $reflection->getConstants();
            
            // Filter out inherited constants
            $classConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);
                return $constant && $constant->getDeclaringClass()->getName() === LivewireBaseForm::class;
            }, ARRAY_FILTER_USE_BOTH);
            
            expect($classConstants)->toBeEmpty();
        });
    });

    describe('documentation and annotations', function () {
        test('has proper PHPDoc annotations', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            $docComment = $reflection->getDocComment();
            
            expect($docComment)->toContain('@template');
            expect($docComment)->toContain('TForm of LivewireBaseForm');
            expect($docComment)->toContain('TFormModel of Model');
            expect($docComment)->toContain('@author');
            expect($docComment)->toContain('@since');
            expect($docComment)->toContain('@see');
            expect($docComment)->toContain('@example');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Base');
        });

        test('follows base class naming convention', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            expect($reflection->getShortName())->toBe('LivewireBaseForm');
            expect($reflection->getName())->toBe('App\\Livewire\\Base\\LivewireBaseForm');
        });
    });

    describe('generic type safety', function () {
        test('provides type safety through generics', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            $docComment = $reflection->getDocComment();
            
            // Should use template generics for type safety
            expect($docComment)->toContain('@template TForm');
            expect($docComment)->toContain('@template TFormModel');
        });
    });

    describe('extensibility hooks', function () {
        test('provides hooks for custom implementations', function () {
            $reflection = new ReflectionClass(LivewireBaseForm::class);
            
            // Check if loadExtraData method exists as extensibility hook
            $methods = $reflection->getMethods();
            $methodNames = array_map(fn($method) => $method->getName(), $methods);
            
            // Should have some mechanism for extensibility
            expect($methodNames)->toContain('loadExtraData');
        });
    });
});