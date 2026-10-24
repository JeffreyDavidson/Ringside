<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSets([
        PestSetList::CODING_STYLE,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_COLLECTION,
    ])
    ->withPreparedSets(earlyReturn: true)
    ->withRules([
        NewlineAfterStatementRector::class,
    ])
    ->withComposerBased(laravel: true);
