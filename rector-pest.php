<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\If_\IfToNullCoalescingAssignRector;
use Rector\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRector;
use RectorLaravel\Rector\Class_\LivewireComponentComputedMethodToComputedAttributeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSets([
        PestSetList::CODING_STYLE,
    ])
    ->withComposerBased(laravel: true)
    ->withSkip([
        BinaryOpNullableToInstanceofRector::class,
        IfToNullCoalescingAssignRector::class,
        LivewireComponentComputedMethodToComputedAttributeRector::class,
        ObjectExplicitBoolCompareRector::class,
    ]);
