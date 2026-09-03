<?php

declare(strict_types=1);

use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasActionColumn;

test('base table behavior composes the action column concern', function () {
    expect(class_uses(BaseTableTrait::class))
        ->toContain(HasActionColumn::class);
});

test('base table behavior supplies additional columns through the protected extension point', function () {
    $method = (new ReflectionClass(BaseTableTrait::class))->getMethod('additionalColumns');

    expect($method->isProtected())->toBeTrue();
});
