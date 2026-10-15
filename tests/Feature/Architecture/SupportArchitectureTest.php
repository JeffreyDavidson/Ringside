<?php

declare(strict_types=1);

test('app support imports nothing from the application outside App\Support', function () {
    // Arrange
    $violations = [];

    // Act
    foreach (glob(app_path('Support/{,*/}*.php'), GLOB_BRACE) ?: [] as $file) {
        preg_match_all('/^use\s+(App\\\\(?!Support\\\\)[^\s;]+)/m', (string) file_get_contents($file), $matches);

        foreach ($matches[1] as $import) {
            $violations[] = basename($file).' imports '.$import;
        }
    }

    // Assert
    expect($violations)->toBeEmpty();
});
