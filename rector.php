<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\New_\NewStaticToNewSelfRector;
use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Transform\Rector\String_\StringToClassConstantRector;
use RectorLaravel\Set\LaravelLevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
    ])
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_130,
    ])
    ->withSkip([
        StringToClassConstantRector::class,
        NewStaticToNewSelfRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        RemoveExtraParametersRector::class,
    ])
    ->withPreparedSets(
        deadCode: false,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    );
// uncomment to reach your current PHP version
// ->withPhpSets()
