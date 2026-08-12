<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
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

arch('concrete business exceptions expose domain-specific inputs')
    ->expect('App\Exceptions')
    ->classes()
    ->not->toUse(Model::class)
    ->ignoring(BaseBusinessException::class);

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

test('application and test code construct business exceptions through factories', function () {
    $violations = [];

    foreach ([app_path(), base_path('tests')] as $directory) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if (str_starts_with($file->getPathname(), app_path('Exceptions').DIRECTORY_SEPARATOR)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                continue;
            }

            $statements = (new ParserFactory())->createForNewestSupportedVersion()->parse($contents);
            $resolvedStatements = (new NodeTraverser(new NameResolver()))->traverse($statements ?? []);

            foreach ((new NodeFinder())->findInstanceOf($resolvedStatements, New_::class) as $construction) {
                if (! $construction->class instanceof Name) {
                    continue;
                }

                $class = $construction->class->toString();

                if (class_exists($class) && is_subclass_of($class, BaseBusinessException::class)) {
                    $relativePath = str($file->getPathname())
                        ->after(base_path().DIRECTORY_SEPARATOR)
                        ->toString();
                    $violations[] = "{$relativePath}:{$construction->getStartLine()} constructs {$class}";
                }
            }
        }
    }

    expect($violations)->toBeEmpty();
});

test('application and test code do not catch generic exception types', function () {
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

    $violations = [];

    foreach ([app_path(), base_path('tests')] as $directory) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if ($file->getRealPath() === __FILE__) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents !== false && $genericCatchTypes($contents) !== []) {
                $violations[] = str($file->getPathname())->after(base_path().DIRECTORY_SEPARATOR)->toString();
            }
        }
    }

    expect($violations)->toBeEmpty();
});

test('every concrete exception factory has an enforced caller', function () {
    $calledFactories = collect([app_path(), base_path('tests')])
        ->flatMap(function (string $directory): array {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            return iterator_to_array($files);
        })
        ->filter(fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php')
        ->flatMap(function (SplFileInfo $file): array {
            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                return [];
            }

            $statements = (new ParserFactory())->createForNewestSupportedVersion()->parse($contents);
            $resolvedStatements = (new NodeTraverser(new NameResolver()))->traverse($statements ?? []);
            $calledFactories = [];

            foreach ((new NodeFinder())->findInstanceOf($resolvedStatements, StaticCall::class) as $call) {
                if (! $call->class instanceof Name || ! $call->name instanceof Identifier) {
                    continue;
                }

                $calledFactories[] = $call->class->toString().'::'.$call->name->toString();
            }

            return $calledFactories;
        })
        ->unique();

    $exceptionFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path('Exceptions'), FilesystemIterator::SKIP_DOTS)
    );
    $exceptionClasses = [];

    foreach ($exceptionFiles as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $exceptionClasses[] = 'App\\Exceptions\\'.str($file->getPathname())
            ->after(app_path('Exceptions').DIRECTORY_SEPARATOR)
            ->beforeLast('.php')
            ->replace(DIRECTORY_SEPARATOR, '\\');
    }

    $orphanedFactories = collect($exceptionClasses)
        ->filter(fn (string $class): bool => class_exists($class) && $class !== BaseBusinessException::class)
        ->flatMap(function (string $class): array {
            return collect((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC))
                ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $class)
                ->map(fn (ReflectionMethod $method): string => $class.'::'.$method->getName())
                ->all();
        })
        ->reject(fn (string $factory): bool => $calledFactories->contains($factory))
        ->values();

    expect($orphanedFactories)->toBeEmpty();
});
