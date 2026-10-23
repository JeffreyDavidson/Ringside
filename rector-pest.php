<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSets([
        PestSetList::CODING_STYLE,
    ])
    ->withRules([
        NewlineAfterStatementRector::class,
    ])
    ->withComposerBased(laravel: true);
