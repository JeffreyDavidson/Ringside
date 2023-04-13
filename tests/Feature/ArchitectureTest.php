<?php

declare(strict_types=1);

test('it will not use dump, dd or ray')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not()->toBeUsed();

test('controllers')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminiate\Http\Request');

test('models')
    ->expect('App\Models')
    ->toOnlyBeUsedIn('App\Repositories')
    ->toOnlyUse('Illuminate\Database')
    ->ignoring('database\seeders');

test('repositories')
    ->expect('App\Repositories')
    ->toOnlyBeUsedIn(['App\Http\Controllers', 'App\Actions', 'App\Listeners'])
    ->toOnlyUse([
        'App\Models',
        'App\Data',
        'Illuminate\Support\Carbon',
        'now',
        'Illuminate\Support\Collection',
        'Illuminate\Database\Eloquent\Collection',
        'App\Enums',
        'Illuminate\Database\Eloquent\Builder',
    ]);
