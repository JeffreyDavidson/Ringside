<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
    ])
    ->withPhpSets()
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_COLLECTION,
    ])
    ->withPreparedSets(earlyReturn: true)
    ->withRules([
        NewlineAfterStatementRector::class,
    ])
    ->withComposerBased(laravel: true);
