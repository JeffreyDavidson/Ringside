<?php

declare(strict_types=1);

use Pest\Rector\Rules\ConvertBeforeAllInDescribeRector;
use Pest\Rector\Rules\FixInvalidRepeatValueRector;
use Pest\Rector\Rules\Pest2ToPest3\TapToDeferRector;
use Pest\Rector\Rules\Pest2ToPest3\ToHaveMethodOnClassRector;
use Pest\Rector\Rules\Pest2ToPest3\UsesToExtendRector;
use Pest\Rector\Rules\RemoveDebugExpectationsRector;
use Pest\Rector\Rules\RemoveOnlyRector;
use Pest\Rector\Rules\RemoveRedundantPestUsesRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withRules([
        TapToDeferRector::class,
        ToHaveMethodOnClassRector::class,
        UsesToExtendRector::class,
        ConvertBeforeAllInDescribeRector::class,
        FixInvalidRepeatValueRector::class,
        RemoveDebugExpectationsRector::class,
        RemoveOnlyRector::class,
        RemoveRedundantPestUsesRector::class,
    ]);
