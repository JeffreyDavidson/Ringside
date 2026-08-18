<?php

declare(strict_types=1);

use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasActionColumn;

test('base table behavior composes the action column concern', function () {
    expect(class_uses(BaseTableTrait::class))
        ->toContain(HasActionColumn::class);
});

test('base table behavior exposes only its configuration and appended columns', function () {
    $methods = collect((new ReflectionClass(BaseTableTrait::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === BaseTableTrait::class)
        ->map(fn (ReflectionMethod $method): string => $method->getName())
        ->sort()
        ->values()
        ->all();

    expect($methods)->toBe(['appendColumns', 'configuringBaseTableTrait']);
});
