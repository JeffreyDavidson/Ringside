<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

test('every semantic colour used by application source is defined as a theme token', function (): void {
    // Arrange
    $theme = file_get_contents(resource_path('css/tokens.css')).file_get_contents(resource_path('css/app.css'));
    preg_match_all('/--color-([a-z0-9-]+)\s*:/', $theme, $definitions);
    // Pure white and black come from Tailwind's default theme.
    $definedColors = array_flip([...$definitions[1], 'white', 'black']);
    $semanticNames = '(?:ringside|legacy|surface|foreground|muted-foreground|border|signal|success|warning|error|primary|outline|white|black|ink|status|footer|overlay)(?:-[a-z0-9]+)*';
    $utilityPattern = '/(?<![\w-])(?:bg|text|border(?:-[xytrblse])?|outline|ring|fill|stroke|divide|placeholder|caret|decoration|from|via|to|shadow|accent)-('.$semanticNames.')(?![\w-])/';
    $variablePattern = '/var\(--color-('.$semanticNames.')(?![\w-])/';
    $undefinedColors = [];

    // Act
    $files = Finder::create()
        ->files()
        ->in([resource_path(), app_path()])
        ->name(['*.php', '*.css', '*.js']);

    foreach ($files as $file) {
        $contents = $file->getContents();
        preg_match_all($utilityPattern, $contents, $utilities);
        preg_match_all($variablePattern, $contents, $variables);

        foreach ([...$utilities[1], ...$variables[1]] as $color) {
            if (array_key_exists($color, $definedColors)) {
                continue;
            }

            $path = $file->getPathname();
            $undefinedColors[$color][$path] = $path;
        }
    }

    ksort($undefinedColors);

    $report = collect($undefinedColors)
        ->map(fn (array $paths, string $color): string => "{$color} (".implode(', ', $paths).')')
        ->values()
        ->all();

    // Assert
    $undefinedList = implode("\n", $report);

    expect($report)->toBeEmpty("Undefined semantic colour tokens:\n{$undefinedList}");
});
