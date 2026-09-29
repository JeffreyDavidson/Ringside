<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

test('application docblocks are attached to a declaration', function (): void {
    // Arrange
    $orphanedDocblocks = [];

    // Act
    foreach (Finder::create()->files()->in(app_path())->name('*.php') as $file) {
        $tokens = token_get_all($file->getContents());

        foreach ($tokens as $index => $token) {
            if (! is_array($token) || $token[0] !== T_DOC_COMMENT) {
                continue;
            }

            $next = null;

            for ($cursor = $index + 1; $cursor < count($tokens); $cursor++) {
                if (is_array($tokens[$cursor]) && $tokens[$cursor][0] === T_WHITESPACE) {
                    continue;
                }

                $next = $tokens[$cursor];

                break;
            }

            if ($next !== '}' && (! is_array($next) || $next[0] !== T_DOC_COMMENT)) {
                continue;
            }

            $orphanedDocblocks[] = "app/{$file->getRelativePathname()}:{$token[2]}";
        }
    }

    // Assert
    expect($orphanedDocblocks)->toBeEmpty(
        'Docblocks followed by another docblock or a closing brace: '.implode(', ', $orphanedDocblocks),
    );
});
