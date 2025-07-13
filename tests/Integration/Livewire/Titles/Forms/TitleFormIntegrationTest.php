<?php

declare(strict_types=1);

use App\Enums\Titles\TitleType;
use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesActivityPeriods;
use App\Livewire\Titles\Forms\TitleForm;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Integration tests for TitleForm component validation and behavior.
 *
 * INTEGRATION TEST SCOPE:
 * - Title name validation with suffix enforcement
 * - TitleType enum validation
 * - Activity period management integration
 * - Title uniqueness constraints
 * - Championship-specific business logic
 * - Data transformation for title entities
 *
 * These tests verify that the TitleForm correctly implements
 * championship title management with proper naming conventions.
 *
 * @see TitleForm
 */
describe('TitleForm Integration Tests', function () {
    beforeEach(function () {
        $mockComponent = mock(Component::class);
        $this->form = new TitleForm($mockComponent, 'form');
    });

    describe('validation rules configuration', function () {
        test('rules method returns title-specific validation structure', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKeys(['name', 'type', 'activation_date']);
        });

        test('title name validation enforces naming conventions', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('string');
            expect($rules['name'])->toContain('max:255');
            
            // Should enforce title suffix
            $hasEndsWithRule = collect($rules['name'])->contains(function ($rule) {
                return is_string($rule) && str_contains($rule, 'ends_with');
            });
            expect($hasEndsWithRule)->toBeTrue();

            // Should have uniqueness constraint
            $hasUniqueRule = collect($rules['name'])->contains(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Unique;
            });
            expect($hasUniqueRule)->toBeTrue();
        });

        test('title type validation enforces enum values', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['type'])->toContain('required');
            
            // Should validate against TitleType enum
            $hasEnumRule = collect($rules['type'])->contains(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Enum;
            });
            expect($hasEnumRule)->toBeTrue();
        });

        test('activation date validation allows future dates', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            expect($rules['activation_date'])->toContain('nullable');
            expect($rules['activation_date'])->toContain('date');
        });
    });

    describe('trait integration', function () {
        test('uses ManagesActivityPeriods trait for title lifecycle', function () {
            expect(TitleForm::class)->usesTrait(ManagesActivityPeriods::class);
        });

        test('activity period integration methods exist', function () {
            $activityMethods = ['getModelActivityPeriods', 'getActivityPeriodsRelationshipName'];

            foreach ($activityMethods as $method) {
                expect(method_exists($this->form, $method))->toBeTrue("Method {$method} should exist");
            }
        });
    });

    describe('data transformation methods', function () {
        test('getModelClass returns correct Title class', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelClass');
            $reflection->setAccessible(true);
            $modelClass = $reflection->invoke($this->form);

            expect($modelClass)->toBe(Title::class);
        });

        test('getModelData transforms title data correctly', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelData');
            $reflection->setAccessible(true);
            
            // Set championship test data
            $this->form->name = 'World Heavyweight Championship';
            $this->form->type = TitleType::SINGLES;
            $this->form->activation_date = '2024-01-01';
            
            $data = $reflection->invoke($this->form);
            
            expect($data)->toBeArray();
            expect($data)->toHaveKeys(['name', 'type', 'activation_date']);
            expect($data['name'])->toBe('World Heavyweight Championship');
            expect($data['type'])->toBe(TitleType::SINGLES);
            expect($data['activation_date'])->toBe('2024-01-01');
        });
    });

    describe('championship business logic', function () {
        test('enforces title naming convention with proper suffix', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Should require titles to end with "Title" or "Championship"
            $endsWithRule = collect($rules['name'])->first(function ($rule) {
                return is_string($rule) && str_contains($rule, 'ends_with');
            });

            expect($endsWithRule)->not->toBeNull();
        });

        test('validates title type against wrestling championship categories', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Should enforce TitleType enum (SINGLES, TAG_TEAM, etc.)
            $enumRule = collect($rules['type'])->first(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Enum;
            });

            expect($enumRule)->not->toBeNull();
        });

        test('enforces title name uniqueness across all titles', function () {
            $reflection = new ReflectionMethod($this->form, 'rules');
            $reflection->setAccessible(true);
            $rules = $reflection->invoke($this->form);

            // Check that unique rule is configured for titles table
            $uniqueRule = collect($rules['name'])->first(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Unique;
            });

            expect($uniqueRule)->not->toBeNull();
        });
    });

    describe('activity period management', function () {
        test('getModelActivityPeriods returns correct relationship', function () {
            $reflection = new ReflectionMethod($this->form, 'getModelActivityPeriods');
            $reflection->setAccessible(true);
            
            // Mock a title model for testing
            $mockTitle = mock(Title::class);
            $this->form->formModel = $mockTitle;
            
            $mockTitle->shouldReceive('activityPeriods')->once()->andReturn(collect());
            
            $periods = $reflection->invoke($this->form);
            expect($periods)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        test('getActivityPeriodsRelationshipName returns correct relationship name', function () {
            $reflection = new ReflectionMethod($this->form, 'getActivityPeriodsRelationshipName');
            $reflection->setAccessible(true);
            
            $relationshipName = $reflection->invoke($this->form);
            expect($relationshipName)->toBe('activityPeriods');
        });
    });

    describe('form inheritance and structure', function () {
        test('extends LivewireBaseForm correctly', function () {
            expect($this->form)->toBeInstanceOf(LivewireBaseForm::class);
        });

        test('implements required abstract methods', function () {
            $requiredMethods = ['getModelClass', 'getModelData', 'rules'];

            foreach ($requiredMethods as $method) {
                expect(method_exists($this->form, $method))->toBeTrue("Method {$method} should exist");
            }
        });

        test('has public form properties for title data', function () {
            $properties = ['name', 'type', 'activation_date'];

            foreach ($properties as $property) {
                expect(property_exists($this->form, $property))->toBeTrue("Property {$property} should exist");
            }
        });
    });

    describe('validation attributes customization', function () {
        test('validationAttributes provides readable field names', function () {
            $reflection = new ReflectionMethod($this->form, 'validationAttributes');
            $reflection->setAccessible(true);
            $attributes = $reflection->invoke($this->form);

            expect($attributes)->toBeArray();
            expect($attributes)->toHaveKey('activation_date');
            expect($attributes['activation_date'])->toBe('activation date');
        });
    });
});