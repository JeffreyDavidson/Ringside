<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withComposerBased(laravel: true);
