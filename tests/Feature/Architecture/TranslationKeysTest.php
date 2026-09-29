<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

test('literal translation keys used by the application resolve to translations', function (): void {
    // Arrange
    $ignoredPaths = [
        // Removed in Task 3.2 (unrendered components)
        'app/Livewire/Matches/Tables/Main.php',
        'app/Livewire/Titles/Tables/TitleChampionshipsTable.php',
    ];
    $pattern = '/(?:(?<![\w$>:])(?:__|trans|trans_choice)|@lang)\(\s*([\'"])((?:[\w-]+::)?[\w-]+(?:\.[\w-]+)+)\1/';
    $missingKeys = [];

    // Act
    $files = Finder::create()
        ->files()
        ->in([app_path(), resource_path('views')])
        ->name(['*.php', '*.blade.php']);

    foreach ($files as $file) {
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()));

        if (in_array($relativePath, $ignoredPaths, true)) {
            continue;
        }

        preg_match_all($pattern, $file->getContents(), $matches);

        foreach ($matches[2] as $key) {
            if (Lang::has($key)) {
                continue;
            }

            $missingKeys[$key][$relativePath] = $relativePath;
        }
    }

    ksort($missingKeys);

    $report = collect($missingKeys)
        ->map(fn (array $paths, string $key): string => "{$key} (".implode(', ', $paths).')')
        ->values()
        ->all();

    // Assert
    $missingList = implode("\n", $report);

    expect($report)->toBeEmpty("Missing translation keys:\n{$missingList}");
});
