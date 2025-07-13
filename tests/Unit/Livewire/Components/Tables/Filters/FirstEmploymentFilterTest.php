<?php

declare(strict_types=1);

use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\Traits\HandlesDates;
use Rappasoft\LaravelLivewireTables\Views\Filters\Traits\HasConfig;
use Rappasoft\LaravelLivewireTables\Views\Filters\Traits\HasOptions;
use Rappasoft\LaravelLivewireTables\Views\Traits\Core\HasWireables;

/**
 * Unit tests for FirstEmploymentFilter custom filter class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Trait integration verification
 * - Property structure and defaults
 * - Constructor parameter handling
 * - Configuration setup
 * - Method signatures and return types
 *
 * @see FirstEmploymentFilter
 * @see \Tests\Integration\Livewire\Components\Tables\Filters\FirstEmploymentFilterTest
 */
describe('FirstEmploymentFilter Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends DateRangeFilter class', function () {
            expect(FirstEmploymentFilter::class)->toExtend(DateRangeFilter::class);
        });

        test('is concrete class', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            expect($reflection->isAbstract())->toBeFalse();
            expect($reflection->isFinal())->toBeFalse();
        });
    });

    describe('trait integration', function () {
        test('uses HandlesDates trait', function () {
            expect(FirstEmploymentFilter::class)->usesTrait(HandlesDates::class);
        });

        test('uses HasConfig trait', function () {
            expect(FirstEmploymentFilter::class)->usesTrait(HasConfig::class);
        });

        test('uses HasOptions trait', function () {
            expect(FirstEmploymentFilter::class)->usesTrait(HasOptions::class);
        });

        test('uses HasWireables trait', function () {
            expect(FirstEmploymentFilter::class)->usesTrait(HasWireables::class);
        });
    });

    describe('property structure', function () {
        test('has filterRelationshipName property', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            
            expect($reflection->hasProperty('filterRelationshipName'))->toBeTrue();
            
            $property = $reflection->getProperty('filterRelationshipName');
            expect($property->isPublic())->toBeTrue();
            expect($property->getType()->getName())->toBe('string');
            expect($property->hasDefaultValue())->toBeTrue();
            expect($property->getDefaultValue())->toBe('');
        });

        test('has filterStartField property', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            
            expect($reflection->hasProperty('filterStartField'))->toBeTrue();
            
            $property = $reflection->getProperty('filterStartField');
            expect($property->isPublic())->toBeTrue();
            expect($property->getType()->getName())->toBe('string');
            expect($property->hasDefaultValue())->toBeTrue();
            expect($property->getDefaultValue())->toBe('');
        });

        test('has filterEndField property', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            
            expect($reflection->hasProperty('filterEndField'))->toBeTrue();
            
            $property = $reflection->getProperty('filterEndField');
            expect($property->isPublic())->toBeTrue();
            expect($property->getType()->getName())->toBe('string');
            expect($property->hasDefaultValue())->toBeTrue();
            expect($property->getDefaultValue())->toBe('');
        });
    });

    describe('constructor behavior', function () {
        test('accepts name and key parameters', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $constructor = $reflection->getConstructor();
            
            expect($constructor)->not->toBeNull();
            expect($constructor->getNumberOfParameters())->toBe(2);
            expect($constructor->getNumberOfRequiredParameters())->toBe(1);
            
            $parameters = $constructor->getParameters();
            expect($parameters[0]->getName())->toBe('name');
            expect($parameters[0]->getType()->getName())->toBe('string');
            expect($parameters[0]->isOptional())->toBeFalse();
            
            expect($parameters[1]->getName())->toBe('key');
            expect($parameters[1]->getType()->getName())->toBe('string');
            expect($parameters[1]->isOptional())->toBeTrue();
            expect($parameters[1]->getDefaultValue())->toBeNull();
        });

        test('calls parent constructor', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('parent::__construct($name, $key);');
        });
    });

    describe('configuration setup', function () {
        test('configures filter with proper options', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('$this->config([');
            expect($source)->toContain("'allowInput' => true");
            expect($source)->toContain("'altFormat' => 'F j, Y'");
            expect($source)->toContain("'ariaDateFormat' => 'F j, Y'");
            expect($source)->toContain("'dateFormat' => 'Y-m-d'");
            expect($source)->toContain("'placeholder' => 'Enter Date Range'");
            expect($source)->toContain("'locale' => 'en'");
        });

        test('sets filter pill values', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('->setFilterPillValues([0 => \'minDate\', 1 => \'maxDate\'])');
        });

        test('configures filter callback', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('->filter(function (Builder $query, array $dateRange): void {');
        });
    });

    describe('setFields method', function () {
        test('has setFields method', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            
            expect($reflection->hasMethod('setFields'))->toBeTrue();
            
            $method = $reflection->getMethod('setFields');
            expect($method->isPublic())->toBeTrue();
            expect($method->getNumberOfParameters())->toBe(3);
            expect($method->getNumberOfRequiredParameters())->toBe(3);
        });

        test('setFields method has correct parameters', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $method = $reflection->getMethod('setFields');
            $parameters = $method->getParameters();
            
            expect($parameters[0]->getName())->toBe('relationshipName');
            expect($parameters[0]->getType()->getName())->toBe('string');
            
            expect($parameters[1]->getName())->toBe('startField');
            expect($parameters[1]->getType()->getName())->toBe('string');
            
            expect($parameters[2]->getName())->toBe('endField');
            expect($parameters[2]->getType()->getName())->toBe('string');
        });

        test('setFields method returns self', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $method = $reflection->getMethod('setFields');
            
            expect($method->getReturnType()->getName())->toBe('self');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Components\\Tables\\Filters');
        });

        test('follows filter naming convention', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            expect($reflection->getShortName())->toBe('FirstEmploymentFilter');
        });
    });

    describe('dependency imports', function () {
        test('imports Carbon for date handling', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Carbon\\Carbon;');
        });

        test('imports Builder for query building', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Illuminate\\Database\\Eloquent\\Builder;');
        });

        test('imports DateRangeFilter base class', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Rappasoft\\LaravelLivewireTables\\Views\\Filters\\DateRangeFilter;');
        });
    });

    describe('filter logic structure', function () {
        test('uses withWhereHas for relationship filtering', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('$query->withWhereHas($this->filterRelationshipName');
        });

        test('implements date range filtering logic', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('->whereBetween(');
            expect($source)->toContain('Carbon::createFromFormat(\'Y-m-d\'');
            expect($source)->toContain('->startOfDay()');
            expect($source)->toContain('->endOfDay()');
        });

        test('handles both start and end field filtering', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('$this->filterStartField');
            expect($source)->toContain('$this->filterEndField');
            expect($source)->toContain('->orWhere(function (Builder $query)');
        });
    });

    describe('employment specific logic', function () {
        test('follows employment filtering pattern', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for employment-specific structure
            expect($source)->toContain('FirstEmploymentFilter');
            expect($source)->toContain('setFields');
        });

        test('handles employment date range logic', function () {
            $reflection = new ReflectionClass(FirstEmploymentFilter::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for proper date range handling in employment context
            expect($source)->toContain('array $dateRange');
            expect($source)->toContain('\'minDate\'');
            expect($source)->toContain('\'maxDate\'');
        });
    });
});