<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;

test('it will not use dump, dd or ray')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not()->toBeUsed();

test('controllers')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Http\Request')
    ->ignoring(AuthenticatedSessionController::class);

// test('models')
//     ->expect('App\Models')
//     ->toOnlyBeUsedIn('App\Repositories')
//     ->toOnlyUse('Illuminate\Database')
//     ->ignoring('Database\Seeders');

// test('repositories')
//     ->expect('App\Repositories')
//     ->toOnlyBeUsedIn(['App\Http\Controllers', 'App\Actions', 'App\Listeners'])
//     ->toOnlyUse([
//         'App\Models',
//         'App\Data',
//         'Illuminate\Support\Carbon',
//         'now',
//         'Illuminate\Support\Collection',
//         'Illuminate\Database\Eloquent\Collection',
//         'App\Enums',
//         'Illuminate\Database\Eloquent\Builder',
//     ]);

test('enums')
    ->expect('App\Enums')
    ->toBeEnums();

test('strict types')
    ->expect('App')
    ->toUseStrictTypes();

test('model traits')
    ->expect('App\Models\Concerns')
    ->toBeTraits();
