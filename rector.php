<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
    ])
    ->withPhpSets()
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
    ])
    ->withComposerBased(laravel: true);
