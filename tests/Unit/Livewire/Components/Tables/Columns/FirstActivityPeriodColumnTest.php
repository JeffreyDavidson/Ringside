<?php

declare(strict_types=1);

use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Models\Stables\Stable;
use App\Models\Titles\Title;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Unit tests for FirstActivityPeriodColumn custom column class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Constructor parameter handling
 * - Column configuration and setup
 * - Type safety and model constraints
 * - Label callback functionality
 *
 * @see FirstActivityPeriodColumn
 * @see \Tests\Integration\Livewire\Components\Tables\Columns\FirstActivityPeriodColumnTest
 */
describe('FirstActivityPeriodColumn Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends Column class', function () {
            expect(FirstActivityPeriodColumn::class)->toExtend(Column::class);
        });

        test('is concrete class', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            expect($reflection->isAbstract())->toBeFalse();
            expect($reflection->isFinal())->toBeFalse();
        });
    });

    describe('constructor behavior', function () {
        test('accepts title and from parameters', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $constructor = $reflection->getConstructor();
            
            expect($constructor)->not->toBeNull();
            expect($constructor->getNumberOfParameters())->toBe(2);
            expect($constructor->getNumberOfRequiredParameters())->toBe(1);
            
            $parameters = $constructor->getParameters();
            expect($parameters[0]->getName())->toBe('title');
            expect($parameters[0]->getType()->getName())->toBe('string');
            expect($parameters[0]->isOptional())->toBeFalse();
            
            expect($parameters[1]->getName())->toBe('from');
            expect($parameters[1]->getType()->getName())->toBe('string');
            expect($parameters[1]->isOptional())->toBeTrue();
            expect($parameters[1]->getDefaultValue())->toBeNull();
        });

        test('calls parent constructor', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $constructor = $reflection->getConstructor();
            
            expect($constructor)->not->toBeNull();
            expect($constructor->isPublic())->toBeTrue();
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Components\\Tables\\Columns');
        });

        test('follows column naming convention', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            expect($reflection->getShortName())->toBe('FirstActivityPeriodColumn');
        });
    });

    describe('model type constraints', function () {
        test('constructor contains model type hints', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $constructor = $reflection->getConstructor();
            $source = file_get_contents($reflection->getFileName());
            
            // Check that the label callback has proper type hints
            expect($source)->toContain('Stable|Title $row');
            expect($source)->toContain('Column $column');
        });

        test('uses expected model classes', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use App\\Models\\Stables\\Stable;');
            expect($source)->toContain('use App\\Models\\Titles\\Title;');
        });
    });

    describe('column configuration', function () {
        test('configures label callback', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $constructor = $reflection->getConstructor();
            $source = file_get_contents($reflection->getFileName());
            
            // Check that the constructor sets up label callback
            expect($source)->toContain('$this->label(');
            expect($source)->toContain('getFormattedFirstActivity()');
        });

        test('uses proper callback structure', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for proper callback structure with type hints
            expect($source)->toContain('fn (Stable|Title $row, Column $column): string =>');
        });
    });

    describe('method dependencies', function () {
        test('relies on getFormattedFirstActivity method', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('$row->getFormattedFirstActivity()');
        });

        test('expects string return type from model method', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check that the callback returns string
            expect($source)->toContain('): string =>');
        });
    });

    describe('laravel livewire tables integration', function () {
        test('uses correct Column base class', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Rappasoft\\LaravelLivewireTables\\Views\\Column;');
        });

        test('follows Laravel Livewire Tables column pattern', function () {
            $reflection = new ReflectionClass(FirstActivityPeriodColumn::class);
            $constructor = $reflection->getConstructor();
            $source = file_get_contents($reflection->getFileName());
            
            // Check that it calls parent constructor and sets up label
            expect($source)->toContain('parent::__construct($title, $from);');
            expect($source)->toContain('$this->label(');
        });
    });
});