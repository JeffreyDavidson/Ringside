<?php

declare(strict_types=1);

use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Unit tests for FirstEmploymentDateColumn custom column class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Constructor parameter handling
 * - Column configuration and setup
 * - Type safety and model constraints
 * - Label callback functionality
 *
 * @see FirstEmploymentDateColumn
 * @see \Tests\Integration\Livewire\Components\Tables\Columns\FirstEmploymentDateColumnTest
 */
describe('FirstEmploymentDateColumn Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends Column class', function () {
            expect(FirstEmploymentDateColumn::class)->toExtend(Column::class);
        });

        test('is concrete class', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            expect($reflection->isAbstract())->toBeFalse();
            expect($reflection->isFinal())->toBeFalse();
        });
    });

    describe('constructor behavior', function () {
        test('accepts title and from parameters', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
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
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $constructor = $reflection->getConstructor();
            
            expect($constructor)->not->toBeNull();
            expect($constructor->isPublic())->toBeTrue();
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Components\\Tables\\Columns');
        });

        test('follows column naming convention', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            expect($reflection->getShortName())->toBe('FirstEmploymentDateColumn');
        });
    });

    describe('model type constraints', function () {
        test('constructor contains model type hints', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $constructor = $reflection->getConstructor();
            $source = file_get_contents($reflection->getFileName());
            
            // Check that the label callback has proper type hints
            expect($source)->toContain('Wrestler|TagTeam|Manager|Referee $row');
            expect($source)->toContain('Column $column');
        });

        test('uses expected model classes', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use App\\Models\\Managers\\Manager;');
            expect($source)->toContain('use App\\Models\\Referees\\Referee;');
            expect($source)->toContain('use App\\Models\\TagTeams\\TagTeam;');
            expect($source)->toContain('use App\\Models\\Wrestlers\\Wrestler;');
        });
    });

    describe('column configuration', function () {
        test('configures label callback', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $constructor = $reflection->getConstructor();
            $source = file_get_contents($reflection->getFileName());
            
            // Check that the constructor sets up label callback
            expect($source)->toContain('$this->label(');
            expect($source)->toContain('getFormattedFirstEmployment()');
        });

        test('uses proper callback structure', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for proper callback structure with type hints
            expect($source)->toContain('fn (Wrestler|TagTeam|Manager|Referee $row, Column $column): string =>');
        });
    });

    describe('method dependencies', function () {
        test('relies on getFormattedFirstEmployment method', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('$row->getFormattedFirstEmployment()');
        });

        test('expects string return type from model method', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check that the callback returns string
            expect($source)->toContain('): string =>');
        });
    });

    describe('laravel livewire tables integration', function () {
        test('uses correct Column base class', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Rappasoft\\LaravelLivewireTables\\Views\\Column;');
        });

        test('follows Laravel Livewire Tables column pattern', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $constructor = $reflection->getConstructor();
            $source = file_get_contents($reflection->getFileName());
            
            // Check that it calls parent constructor and sets up label
            expect($source)->toContain('parent::__construct($title, $from);');
            expect($source)->toContain('$this->label(');
        });
    });

    describe('employable model support', function () {
        test('supports all employable model types', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check that it supports all employable entities
            expect($source)->toContain('Wrestler|TagTeam|Manager|Referee');
        });

        test('aligns with employment business rules', function () {
            $reflection = new ReflectionClass(FirstEmploymentDateColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check that it calls employment-specific method
            expect($source)->toContain('getFormattedFirstEmployment()');
        });
    });
});