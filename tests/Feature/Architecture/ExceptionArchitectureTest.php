<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

arch('business exceptions use the shared exception foundation')
    ->expect('App\Exceptions')
    ->classes()
    ->toExtend(BaseBusinessException::class)
    ->ignoring(BaseBusinessException::class);

arch('concrete business exceptions are final')
    ->expect('App\Exceptions')
    ->classes()
    ->toBeFinal()
    ->ignoring(BaseBusinessException::class);

arch('business exceptions use the exception suffix')
    ->expect('App\Exceptions')
    ->classes()
    ->toHaveSuffix('Exception');

test('the business exception foundation is abstract', function () {
    $reflection = new ReflectionClass(BaseBusinessException::class);

    expect($reflection->isAbstract())->toBeTrue();
});

test('roster exceptions belong to a roster entity boundary', function () {
    expect(glob(app_path('Exceptions/Roster/*.php')) ?: [])->toBeEmpty();
});

test('exceptions do not use technical catch-all directories', function () {
    expect(glob(app_path('Exceptions/Data/*.php')) ?: [])->toBeEmpty()
        ->and(glob(app_path('Exceptions/BusinessRules/*.php')) ?: [])->toBeEmpty();
});

test('application code does not directly construct generic exceptions', function () {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS)
    );

    $violations = [];

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo) {
            continue;
        }

        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        if ($file->getPathname() === app_path('Exceptions/BaseBusinessException.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if ($contents !== false && preg_match('/throw\s+new\s+\\?Exception\s*\(/', $contents) === 1) {
            $violations[] = str($file->getPathname())->after(app_path().DIRECTORY_SEPARATOR)->toString();
        }
    }

    expect($violations)->toBeEmpty();
});

test('Livewire components do not catch generic exception types', function () {
    $genericCatchTypes = function (string $contents): array {
        $statements = (new ParserFactory())->createForNewestSupportedVersion()->parse($contents);
        $resolvedStatements = (new NodeTraverser(new NameResolver()))->traverse($statements ?? []);
        $catchClauses = (new NodeFinder())->findInstanceOf($resolvedStatements, Catch_::class);

        return collect($catchClauses)
            ->flatMap(fn (Catch_ $catchClause): array => $catchClause->types)
            ->map(fn ($type): string => $type->toString())
            ->intersect([Exception::class, Throwable::class])
            ->values()
            ->all();
    };

    expect($genericCatchTypes(<<<'PHP'
        <?php

        use Exception as GenericException;

        try {
            // Execute an operation.
        } catch (GenericException) {
            // Handle the exception.
        }
        PHP))->toBe([Exception::class]);

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path('Livewire'), FilesystemIterator::SKIP_DOTS)
    );
    $violations = [];

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if ($contents !== false && $genericCatchTypes($contents) !== []) {
            $violations[] = str($file->getPathname())->after(app_path().DIRECTORY_SEPARATOR)->toString();
        }
    }

    expect($violations)->toBeEmpty();
});
