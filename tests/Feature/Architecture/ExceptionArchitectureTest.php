<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;

arch('business exceptions use the shared exception foundation')
    ->expect('App\Exceptions')
    ->classes()
    ->toExtend(BaseBusinessException::class)
    ->ignoring(BaseBusinessException::class);

arch('concrete business exceptions are final')
    ->expect('App\Exceptions')
    ->classes()
    ->toBeFinal()
    ->ignoring(BaseBusinessException::class);

arch('business exceptions use the exception suffix')
    ->expect('App\Exceptions')
    ->classes()
    ->toHaveSuffix('Exception');

test('the business exception foundation is abstract', function () {
    $reflection = new ReflectionClass(BaseBusinessException::class);

    expect($reflection->isAbstract())->toBeTrue();
});

test('roster exceptions belong to a roster entity boundary', function () {
    expect(glob(app_path('Exceptions/Roster/*.php')) ?: [])->toBeEmpty();
});

test('exceptions do not use technical catch-all directories', function () {
    expect(glob(app_path('Exceptions/Data/*.php')) ?: [])->toBeEmpty()
        ->and(glob(app_path('Exceptions/BusinessRules/*.php')) ?: [])->toBeEmpty();
});
