<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

test('literal translation keys used by the application resolve to translations', function (): void {
    // Arrange
    $pattern = '/(?:(?<![\w$>:])(?:__|trans|trans_choice)|@lang|(?<=::)forModel)\(\s*([\'"])((?:[\w-]+::)?[\w-]+(?:\.[\w-]+)+)\1/';
    $missingKeys = [];

    // Act
    $files = Finder::create()
        ->files()
        ->in([app_path(), resource_path('views')])
        ->name(['*.php', '*.blade.php']);

    foreach ($files as $file) {
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()));

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

test('every business exception message is built from a translation key', function (): void {
    // Arrange
    $isTranslated = fn (?Expr $message): bool => $message instanceof Variable
        || ($message instanceof FuncCall && $message->name instanceof Name && in_array($message->name->toString(), ['__', 'trans_choice'], true));
    $hardCoded = [];
    $files = Finder::create()
        ->files()
        ->in(app_path('Exceptions'))
        ->name('*.php');

    // Act
    foreach ($files as $file) {
        $statements = (new ParserFactory)->createForNewestSupportedVersion()->parse($file->getContents()) ?? [];
        $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
        $finder = new NodeFinder;

        $constructed = array_map(
            fn (New_ $construction): ?Expr => ($construction->args[0] ?? null) instanceof Arg ? $construction->args[0]->value : null,
            $finder->findInstanceOf($statements, New_::class),
        );
        $withReason = array_map(
            fn (StaticCall $call): ?Expr => ($call->args[1] ?? null) instanceof Arg ? $call->args[1]->value : null,
            array_filter(
                $finder->findInstanceOf($statements, StaticCall::class),
                fn (StaticCall $call): bool => $call->name instanceof Identifier && $call->name->toString() === 'forReason',
            ),
        );

        foreach ([...$constructed, ...$withReason] as $message) {
            if ($message instanceof Expr && ! $isTranslated($message)) {
                $hardCoded[] = "{$relativePath}:{$message->getStartLine()}";
            }
        }
    }

    // Assert
    $hardCodedList = implode("\n", $hardCoded);

    expect($hardCoded)->toBeEmpty("Exception messages that are not translated:\n{$hardCodedList}");
});

test('every business exception translates a fixed key, never one passed in by the caller', function (): void {
    // Arrange
    $dynamicKeys = [];
    $files = Finder::create()
        ->files()
        ->in(app_path('Exceptions'))
        ->name('*.php');

    // Act
    foreach ($files as $file) {
        $statements = (new ParserFactory)->createForNewestSupportedVersion()->parse($file->getContents()) ?? [];
        $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());

        // BaseBusinessException::forModel() translates the key its callers pass, so the callers are checked instead.
        $translations = $file->getFilename() === 'BaseBusinessException.php' ? [] : array_filter(
            (new NodeFinder)->findInstanceOf($statements, FuncCall::class),
            fn (FuncCall $call): bool => $call->name instanceof Name && in_array($call->name->toString(), ['__', 'trans_choice'], true),
        );

        $modelFactories = array_filter(
            (new NodeFinder)->findInstanceOf($statements, StaticCall::class),
            fn (StaticCall $call): bool => $call->name instanceof Identifier && $call->name->toString() === 'forModel',
        );

        foreach ($modelFactories as $call) {
            $key = ($call->args[0] ?? null) instanceof Arg ? $call->args[0]->value : null;

            if (! $key instanceof String_) {
                $dynamicKeys[] = "{$relativePath}:{$call->getStartLine()}";
            }
        }

        foreach ($translations as $call) {
            $key = ($call->args[0] ?? null) instanceof Arg ? $call->args[0]->value : null;

            if (! $key instanceof String_) {
                $dynamicKeys[] = "{$relativePath}:{$call->getStartLine()}";
            }
        }
    }

    // Assert
    $dynamicList = implode("\n", $dynamicKeys);

    expect($dynamicKeys)->toBeEmpty("Exception translation keys that are not fixed strings:\n{$dynamicList}");
});

test('every enum label resolves to a translation', function (): void {
    // Arrange
    $untranslated = [];
    $files = Finder::create()
        ->files()
        ->in(app_path('Enums'))
        ->name('*.php');

    // Act
    foreach ($files as $file) {
        $class = 'App\\Enums\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

        if (! enum_exists($class) || ! method_exists($class, 'label')) {
            continue;
        }

        foreach ($class::cases() as $case) {
            // A missing key makes __() return the key itself, such as "titles.status.active".
            if (preg_match('/^[\w-]+(\.[\w-]+)+$/', $case->label()) === 1) {
                $untranslated[] = "{$class}::{$case->name} -> {$case->label()}";
            }
        }
    }

    // Assert
    $untranslatedList = implode("\n", $untranslated);

    expect($untranslated)->toBeEmpty("Enum labels without a translation:\n{$untranslatedList}");
});
