<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

test('every Ringside colour used by the views is defined as a theme token', function (): void {
    // Arrange
    preg_match_all('/--color-(ringside-[a-z-]+)\s*:/', (string) file_get_contents(resource_path('css/tokens.css')), $definitions);
    $definedColors = array_flip($definitions[1]);
    $utilityPattern = '/(?<![\w-])(?:bg|text|border(?:-[xytrblse])?|outline|ring|fill|stroke|divide|placeholder|caret|decoration|from|via|to|shadow|accent)-(ringside-[a-z]+(?:-[a-z]+)*)/';
    $variablePattern = '/var\(--color-(ringside-[a-z-]+)\)/';
    $undefinedColors = [];

    // Act
    $files = Finder::create()
        ->files()
        ->in(resource_path('views'))
        ->name('*.blade.php');

    foreach ($files as $file) {
        $contents = $file->getContents();
        preg_match_all($utilityPattern, $contents, $utilities);
        preg_match_all($variablePattern, $contents, $variables);

        foreach ([...$utilities[1], ...$variables[1]] as $color) {
            if (array_key_exists($color, $definedColors)) {
                continue;
            }

            $undefinedColors[$color][$file->getRelativePathname()] = $file->getRelativePathname();
        }
    }

    ksort($undefinedColors);

    $report = collect($undefinedColors)
        ->map(fn (array $paths, string $color): string => "{$color} (".implode(', ', $paths).')')
        ->values()
        ->all();

    // Assert
    $undefinedList = implode("\n", $report);

    expect($report)->toBeEmpty("Undefined Ringside colour tokens:\n{$undefinedList}");
});
