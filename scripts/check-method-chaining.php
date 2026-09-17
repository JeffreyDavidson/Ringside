<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = array_slice($argv, 1);
$ignoredDirectories = ['.git', 'node_modules', 'storage', 'vendor'];

if ($paths === []) {
    $paths = ['.'];
}

$files = [];

foreach ($paths as $path) {
    $absolutePath = $root.'/'.ltrim($path, '/');

    if (is_file($absolutePath)) {
        $files[] = $absolutePath;
        continue;
    }

    if (! is_dir($absolutePath)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absolutePath, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

$violations = [];

foreach (array_unique($files) as $file) {
    $relativePath = substr($file, strlen($root) + 1);
    $segments = explode(DIRECTORY_SEPARATOR, $relativePath);

    if (array_intersect($segments, $ignoredDirectories) !== []) {
        continue;
    }

    $tokens = token_get_all((string) file_get_contents($file));
    $operatorsByLine = [];

    foreach ($tokens as $token) {
        if (is_array($token) && $token[0] === T_OBJECT_OPERATOR) {
            $operatorsByLine[$token[2]] = ($operatorsByLine[$token[2]] ?? 0) + 1;
        }
    }

    foreach ($operatorsByLine as $line => $count) {
        if ($count > 1) {
            $violations[] = sprintf('%s:%d contains %d chained calls on one line.', $relativePath, $line, $count);
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Method chains must put each call on its own line:\n".implode("\n", $violations)."\n");
    exit(1);
}

echo "Method-chain formatting passed.\n";
