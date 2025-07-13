<?php

declare(strict_types=1);

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\BaseModal;
use App\Livewire\Concerns\GeneratesDummyData;
use Livewire\Component;

/**
 * Unit tests for BaseFormModal abstract class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Trait integration verification
 * - Abstract method existence and signatures
 * - Property visibility and types
 * - Class constants and configuration
 *
 * @see BaseFormModal
 * @see \Tests\Integration\Livewire\Base\BaseFormModalTest
 */
describe('BaseFormModal Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends BaseModal class', function () {
            expect(BaseFormModal::class)->toExtend(BaseModal::class);
        });

        test('extends Livewire Component', function () {
            expect(BaseFormModal::class)->toExtend(Component::class);
        });
    });

    describe('trait integration', function () {
        test('uses GeneratesDummyData trait', function () {
            expect(BaseFormModal::class)->usesTrait(GeneratesDummyData::class);
        });
    });

    describe('abstract methods', function () {
        test('has required abstract methods', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            
            expect($reflection->isAbstract())->toBeTrue();
            
            $abstractMethods = $reflection->getMethods(ReflectionMethod::IS_ABSTRACT);
            $abstractMethodNames = array_map(fn($method) => $method->getName(), $abstractMethods);
            
            expect($abstractMethodNames)->toContain('getFormClass');
            expect($abstractMethodNames)->toContain('getModelClass');
            expect($abstractMethodNames)->toContain('getModalPath');
        });
    });

    describe('method signatures', function () {
        test('abstract methods have correct signatures', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            
            // getFormClass method
            $getFormClass = $reflection->getMethod('getFormClass');
            expect($getFormClass->isAbstract())->toBeTrue();
            expect($getFormClass->isProtected())->toBeTrue();
            expect($getFormClass->getReturnType()->getName())->toBe('string');
            
            // getModelClass method
            $getModelClass = $reflection->getMethod('getModelClass');
            expect($getModelClass->isAbstract())->toBeTrue();
            expect($getModelClass->isProtected())->toBeTrue();
            expect($getModelClass->getReturnType()->getName())->toBe('string');
            
            // getModalPath method
            $getModalPath = $reflection->getMethod('getModalPath');
            expect($getModalPath->isAbstract())->toBeTrue();
            expect($getModalPath->isProtected())->toBeTrue();
            expect($getModalPath->getReturnType()->getName())->toBe('string');
        });
    });

    describe('template method pattern', function () {
        test('class is designed as template method pattern', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            
            // Should be abstract (template)
            expect($reflection->isAbstract())->toBeTrue();
            
            // Should have abstract methods for child configuration
            $abstractMethods = $reflection->getMethods(ReflectionMethod::IS_ABSTRACT);
            expect($abstractMethods)->not->toBeEmpty();
            
            // Should have concrete methods for common functionality
            $concreteMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
            $concreteMethodNames = array_map(fn($method) => $method->getName(), $concreteMethods);
            expect($concreteMethodNames)->toContain('mount');
        });
    });

    describe('class constants', function () {
        test('has no class-specific constants', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            $constants = $reflection->getConstants();
            
            // Filter out inherited constants
            $classConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);
                return $constant && $constant->getDeclaringClass()->getName() === BaseFormModal::class;
            }, ARRAY_FILTER_USE_BOTH);
            
            expect($classConstants)->toBeEmpty();
        });
    });

    describe('documentation and annotations', function () {
        test('has proper PHPDoc annotations', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            $docComment = $reflection->getDocComment();
            
            expect($docComment)->toContain('@template');
            expect($docComment)->toContain('TForm of LivewireBaseForm');
            expect($docComment)->toContain('TModel of Model');
            expect($docComment)->toContain('@extends BaseModal');
            expect($docComment)->toContain('@see BaseModal');
            expect($docComment)->toContain('@see GeneratesDummyData');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Base');
        });

        test('follows base class naming convention', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            expect($reflection->getShortName())->toBe('BaseFormModal');
            expect($reflection->getName())->toBe('App\\Livewire\\Base\\BaseFormModal');
        });
    });

    describe('generic type safety', function () {
        test('provides type safety through generics', function () {
            $reflection = new ReflectionClass(BaseFormModal::class);
            $docComment = $reflection->getDocComment();
            
            // Should use template generics for type safety
            expect($docComment)->toContain('@template TForm');
            expect($docComment)->toContain('@template TModel');
            expect($docComment)->toContain('@extends BaseModal<TForm, TModel>');
        });
    });

    describe('composition over inheritance', function () {
        test('uses traits for specialized functionality', function () {
            $traits = class_uses_recursive(BaseFormModal::class);
            
            expect($traits)->toContain('App\\Livewire\\Concerns\\GeneratesDummyData');
        });
    });
});