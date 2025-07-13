<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;

/**
 * Architecture Tests for Ringside Application
 *
 * These tests enforce architectural rules and coding standards across the codebase.
 * They ensure consistency, maintainability, and adherence to Laravel best practices.
 *
 * @see https://pestphp.com/docs/arch-testing
 */

// =============================================================================
// PRESET ARCHITECTURE TESTS
// =============================================================================

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

// =============================================================================
// CORE ARCHITECTURE RULES
// =============================================================================

arch('it will not use dump, dd or ray')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not()->toBeUsed();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toBeFinal()
    ->not->toUse('Illuminate\Http\Request')
    ->ignoring(AuthenticatedSessionController::class);

test('enums')
    ->expect('App\Enums')
    ->toBeEnums();

test('strict types')
    ->expect('App')
    ->toUseStrictTypes();

// =============================================================================
// NAMING CONVENTIONS
// =============================================================================

arch('actions are properly named')
    ->expect('App\\Actions')
    ->toHaveSuffix('Action');

arch('repositories implement contracts')
    ->expect('App\\Repositories')
    ->toImplement('App\\Repositories\\Contracts');

arch('controller classes should have proper suffix')
    ->expect('App\\Http\\Controllers')
    ->toHaveSuffix('Controller');

arch('service classes should have proper suffix')
    ->expect('App\\Services')
    ->toHaveSuffix('Service');

arch('test files are properly named')
    ->expect('Tests')
    ->toHaveSuffix('Test.php');

// =============================================================================
// DEPENDENCY RULES
// =============================================================================

arch('no facades in domain or actions')
    ->expect(['App\\Actions', 'App\\Models'])
    ->not->toUse('Illuminate\\Support\\Facades\\*');

arch('controllers do not use DB facade')
    ->expect('App\\Http\\Controllers')
    ->not->toUse('Illuminate\\Support\\Facades\\DB');

arch('controllers do not use models directly')
    ->expect('App\\Http\\Controllers')
    ->not->toUse('App\\Models\\*');

arch('models are only used in repositories')
    ->expect('App\\Models')
    ->toOnlyBeUsedIn('App\\Repositories')
    ->ignoring('App\\Models\\Traits');

// =============================================================================
// CLASS STRUCTURE RULES
// =============================================================================

arch('action classes should be invokable')
    ->expect('App\\Actions')
    ->toBeInvokable();

arch('job classes should have handle method')
    ->expect('App\\Jobs')
    ->toHaveMethod('handle');

arch('models extend base model')
    ->expect('App\\Models')
    ->toExtend('Illuminate\\Database\\Eloquent\\Model')
    ->ignoring('App\\Models\\Traits');

arch('commands')
    ->expect('App\\Console\\Commands')
    ->toBeClasses()
    ->toUseStrictTypes()
    ->toExtend('Illuminate\\Console\\Command')
    ->toHaveMethod('handle');

arch('traits are only in Concerns directories')
    ->expect('App\\*\\Concerns')
    ->toBeTraits();

arch('interfaces namespace only contains interfaces')
    ->expect('App\\Interfaces')
    ->toBeInterfaces();

// =============================================================================
// SECURITY & DEBUGGING RULES
// =============================================================================

arch('do not use env helper in code')
    ->expect(['env'])
    ->not->toBeUsed();

arch('no debug functions')
    ->expect(['dd', 'dump', 'var_dump', 'ray', 'sleep'])
    ->not->toBeUsed();

arch('do not access session/auth/request in async jobs')
    ->expect([
        'session',
        'auth',
        'request',
        'Illuminate\\Support\\Facades\\Auth',
        'Illuminate\\Support\\Facades\\Session',
        'Illuminate\\Http\\Request',
        'Illuminate\\Support\\Facades\\Request'
    ])
    ->each->not->toBeUsedIn('App\\Jobs');
