<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

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
