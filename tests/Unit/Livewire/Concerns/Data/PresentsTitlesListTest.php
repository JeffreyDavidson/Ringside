<?php

declare(strict_types=1);

use App\Livewire\Concerns\Data\PresentsTitlesList;
use App\Models\Titles\Title;
use Livewire\Attributes\Computed;

/**
 * Unit tests for PresentsTitlesList trait structure.
 *
 * UNIT TEST SCOPE:
 * - Trait structure verification
 * - Method signatures and return types
 * - Method visibility and attributes
 * - Trait naming and namespace
 * - Livewire computed attribute usage
 *
 * @see PresentsTitlesList
 * @see \Tests\Integration\Livewire\Concerns\Data\PresentsTitlesListTest
 */
describe('PresentsTitlesList Unit Tests', function () {
    describe('trait structure', function () {
        test('is trait', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            expect($reflection->isTrait())->toBeTrue();
        });

        test('is not abstract', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            expect($reflection->isAbstract())->toBeFalse();
        });
    });

    describe('method signatures', function () {
        test('has getTitles method', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            
            expect($reflection->hasMethod('getTitles'))->toBeTrue();
            
            $method = $reflection->getMethod('getTitles');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('array');
            expect($method->getNumberOfParameters())->toBe(0);
        });
    });

    describe('method annotations', function () {
        test('getTitles has Computed attribute', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            $method = $reflection->getMethod('getTitles');
            $attributes = $method->getAttributes();
            
            expect($attributes)->toHaveCount(1);
            expect($attributes[0]->getName())->toBe('Livewire\\Attributes\\Computed');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Concerns\\Data');
        });

        test('follows trait naming convention', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            expect($reflection->getShortName())->toBe('PresentsTitlesList');
        });
    });

    describe('dependency imports', function () {
        test('imports Title model', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use App\\Models\\Titles\\Title;');
        });

        test('imports Computed attribute', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Livewire\\Attributes\\Computed;');
        });
    });

    describe('trait method organization', function () {
        test('has single public method', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            $methods = array_filter(
                $reflection->getMethods(),
                fn($method) => $method->getDeclaringClass()->getName() === PresentsTitlesList::class
            );
            
            expect($methods)->toHaveCount(1);
            expect($methods[0]->getName())->toBe('getTitles');
            expect($methods[0]->isPublic())->toBeTrue();
        });
    });

    describe('trait simplicity', function () {
        test('is minimal focused trait', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            
            // Should have minimal methods (just the getter)
            $methods = array_filter(
                $reflection->getMethods(),
                fn($method) => $method->getDeclaringClass()->getName() === PresentsTitlesList::class
            );
            
            expect($methods)->toHaveCount(1);
        });

        test('has no properties', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            $properties = array_filter(
                $reflection->getProperties(),
                fn($property) => $property->getDeclaringClass()->getName() === PresentsTitlesList::class
            );
            
            expect($properties)->toHaveCount(0);
        });
    });

    describe('data presentation pattern', function () {
        test('follows data presentation trait pattern', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            
            // Should follow Presents[Entity]List naming pattern
            expect($reflection->getShortName())->toMatch('/^Presents.*List$/');
        });

        test('provides array output for dropdowns', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            $method = $reflection->getMethod('getTitles');
            
            // Should return array suitable for form dropdowns
            expect($method->getReturnType()->getName())->toBe('array');
        });
    });

    describe('livewire integration', function () {
        test('uses Livewire Computed attribute', function () {
            $reflection = new ReflectionClass(PresentsTitlesList::class);
            $method = $reflection->getMethod('getTitles');
            $attributes = $method->getAttributes(Computed::class);
            
            expect($attributes)->toHaveCount(1);
        });
    });
});