<?php

declare(strict_types=1);

use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasActionColumn;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Unit tests for BaseTableTrait structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Trait integration verification
 * - Property structure and defaults
 * - Method signatures and return types
 * - Configuration method existence
 * - Array type annotations
 *
 * @see BaseTableTrait
 * @see \Tests\Integration\Livewire\Concerns\BaseTableTraitTest
 */
describe('BaseTableTrait Unit Tests', function () {
    describe('trait integration', function () {
        test('uses HasActionColumn trait', function () {
            expect(BaseTableTrait::class)->usesTrait(HasActionColumn::class);
        });
    });

    describe('property structure', function () {
        test('has actionLinksToDisplay property with correct type', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            
            expect($reflection->hasProperty('actionLinksToDisplay'))->toBeTrue();
            
            $property = $reflection->getProperty('actionLinksToDisplay');
            expect($property->isProtected())->toBeTrue();
            expect($property->getType()->getName())->toBe('array');
            expect($property->hasDefaultValue())->toBeTrue();
            expect($property->getDefaultValue())->toEqual(['view' => true, 'edit' => true, 'delete' => true]);
            expect($property->getDocComment())->toContain('@var array<string, bool>');
        });

        test('has showActionColumn property', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            
            expect($reflection->hasProperty('showActionColumn'))->toBeTrue();
            
            $property = $reflection->getProperty('showActionColumn');
            expect($property->isProtected())->toBeTrue();
            expect($property->getType()->getName())->toBe('bool');
            expect($property->hasDefaultValue())->toBeTrue();
            expect($property->getDefaultValue())->toBeTrue();
        });

        test('has configuration string properties', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            
            $stringProperties = ['databaseTableName', 'routeBasePath', 'resourceName'];
            
            foreach ($stringProperties as $propertyName) {
                expect($reflection->hasProperty($propertyName))->toBeTrue();
                
                $property = $reflection->getProperty($propertyName);
                expect($property->isProtected())->toBeTrue();
                expect($property->getType()->getName())->toBe('string');
                expect($property->hasDefaultValue())->toBeTrue();
                expect($property->getDefaultValue())->toBe('');
            }
        });
    });

    describe('method signatures', function () {
        test('has configuringBaseTableTrait method', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            
            expect($reflection->hasMethod('configuringBaseTableTrait'))->toBeTrue();
            
            $method = $reflection->getMethod('configuringBaseTableTrait');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
            expect($method->getNumberOfParameters())->toBe(0);
        });

        test('has appendColumns method', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            
            expect($reflection->hasMethod('appendColumns'))->toBeTrue();
            
            $method = $reflection->getMethod('appendColumns');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('array');
            expect($method->getNumberOfParameters())->toBe(0);
            expect($method->getDocComment())->toContain('@return array<Column>');
        });

        test('has private helper methods', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            
            expect($reflection->hasMethod('setupTableStructure'))->toBeTrue();
            expect($reflection->hasMethod('setSearchAttributes'))->toBeTrue();
            
            $setupMethod = $reflection->getMethod('setupTableStructure');
            expect($setupMethod->isPrivate())->toBeTrue();
            expect($setupMethod->getReturnType()->getName())->toBe('void');
            
            $searchMethod = $reflection->getMethod('setSearchAttributes');
            expect($searchMethod->isPrivate())->toBeTrue();
            expect($searchMethod->getReturnType()->getName())->toBe('void');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Concerns');
        });

        test('follows trait naming convention', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            expect($reflection->getShortName())->toBe('BaseTableTrait');
        });

        test('is trait', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            expect($reflection->isTrait())->toBeTrue();
        });
    });

    describe('dependency imports', function () {
        test('imports required dependencies', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use App\\Livewire\\Concerns\\Columns\\HasActionColumn;');
            expect($source)->toContain('use Rappasoft\\LaravelLivewireTables\\Views\\Column;');
        });
    });

    describe('method implementation structure', function () {
        test('configuringBaseTableTrait contains expected method calls', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for expected configuration calls
            expect($source)->toContain('->setPrimaryKey(\'id\')');
            expect($source)->toContain('->setColumnSelectDisabled()');
            expect($source)->toContain('->setPaginationEnabled()');
            expect($source)->toContain('->setFiltersStatus(true)');
            expect($source)->toContain('$this->setSearchAttributes()');
            expect($source)->toContain('$this->setupTableStructure()');
        });

        test('appendColumns method structure', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for conditional action column logic
            expect($source)->toContain('$this->showActionColumn');
            expect($source)->toContain('$this->getDefaultActionColumn()');
        });
    });

    describe('laravel livewire tables integration', function () {
        test('uses Laravel Livewire Tables components', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('Rappasoft\\LaravelLivewireTables\\Views\\Column');
        });

        test('follows Laravel Livewire Tables patterns', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for standard table configuration patterns
            expect($source)->toContain('configuringBaseTableTrait');
            expect($source)->toContain('appendColumns');
            expect($source)->toContain('setPerPageAccepted');
            expect($source)->toContain('setLoadingPlaceholder');
        });
    });

    describe('trait method organization', function () {
        test('has public interface methods', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            $publicMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                fn($method) => $method->getDeclaringClass()->getName() === BaseTableTrait::class
            );
            
            $publicMethodNames = array_map(fn($method) => $method->getName(), $publicMethods);
            
            expect($publicMethodNames)->toContain('configuringBaseTableTrait');
            expect($publicMethodNames)->toContain('appendColumns');
        });

        test('has private helper methods', function () {
            $reflection = new ReflectionClass(BaseTableTrait::class);
            $privateMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PRIVATE),
                fn($method) => $method->getDeclaringClass()->getName() === BaseTableTrait::class
            );
            
            $privateMethodNames = array_map(fn($method) => $method->getName(), $privateMethods);
            
            expect($privateMethodNames)->toContain('setupTableStructure');
            expect($privateMethodNames)->toContain('setSearchAttributes');
        });
    });
});