<?php

declare(strict_types=1);

use Pest\Rector\Rules\ConvertAssertToExpectRector;
use Pest\Rector\Rules\ConvertBeforeAllInDescribeRector;
use Pest\Rector\Rules\ConvertExpectExceptionToThrowRector;
use Pest\Rector\Rules\EnsureTypeChecksFirstRector;
use Pest\Rector\Rules\FixInvalidRepeatValueRector;
use Pest\Rector\Rules\Pest2ToPest3\TapToDeferRector;
use Pest\Rector\Rules\Pest2ToPest3\ToHaveMethodOnClassRector;
use Pest\Rector\Rules\Pest2ToPest3\UsesToExtendRector;
use Pest\Rector\Rules\RemoveDebugExpectationsRector;
use Pest\Rector\Rules\RemoveOnlyRector;
use Pest\Rector\Rules\RemoveRedundantLiteralTypeExpectationRector;
use Pest\Rector\Rules\RemoveRedundantPestUsesRector;
use Pest\Rector\Rules\RemoveStaticTestClosureRector;
use Pest\Rector\Rules\SimplifyExpectNotRector;
use Pest\Rector\Rules\UseInstanceOfMatcherRector;
use Pest\Rector\Rules\UseStrictEqualityMatchersRector;
use Pest\Rector\Rules\UseToEndWithRector;
use Pest\Rector\Rules\UseToHaveCountRector;
use Pest\Rector\Rules\UseToHaveKeyRector;
use Pest\Rector\Rules\UseToHaveKeysRector;
use Pest\Rector\Rules\UseToStartWithRector;
use Pest\Rector\Rules\UseToThrowRector;
use Pest\Rector\Rules\UseTypeMatchersRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withRules([
        TapToDeferRector::class,
        ToHaveMethodOnClassRector::class,
        UsesToExtendRector::class,
        ConvertAssertToExpectRector::class,
        ConvertExpectExceptionToThrowRector::class,
        ConvertBeforeAllInDescribeRector::class,
        FixInvalidRepeatValueRector::class,
        RemoveDebugExpectationsRector::class,
        RemoveOnlyRector::class,
        RemoveRedundantLiteralTypeExpectationRector::class,
        RemoveRedundantPestUsesRector::class,
        RemoveStaticTestClosureRector::class,
        SimplifyExpectNotRector::class,
        UseInstanceOfMatcherRector::class,
        UseStrictEqualityMatchersRector::class,
        UseToEndWithRector::class,
        UseToHaveCountRector::class,
        UseToHaveKeyRector::class,
        UseToHaveKeysRector::class,
        UseToStartWithRector::class,
        UseToThrowRector::class,
        UseTypeMatchersRector::class,
        EnsureTypeChecksFirstRector::class,
    ]);
