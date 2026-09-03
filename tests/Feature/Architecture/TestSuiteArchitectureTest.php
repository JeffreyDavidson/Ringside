<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

test('browser tests use Pest Browser instead of Laravel Dusk', function (): void {
    $browserFiles = iterator_to_array(
        Finder::create()->files()->in(base_path('tests/Browser'))->name('*.php'),
    );

    expect($browserFiles)->not->toBeEmpty();

    foreach ($browserFiles as $file) {
        $source = $file->getContents();

        expect($source)
            ->toContain('visit(')
            ->not->toContain('Laravel\\Dusk')
            ->not->toContain('DuskTestCase');
    }
});

test('Pest Browser tests remain isolated in the Browser suite', function (): void {
    $misplacedBrowserTests = [];

    foreach (Finder::create()->files()->in(base_path('tests'))->name('*.php') as $file) {
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());

        if (
            str_starts_with($relativePath, 'Browser/')
            || $relativePath === 'Feature/Architecture/TestSuiteArchitectureTest.php'
        ) {
            continue;
        }

        if (! str_contains($file->getContents(), 'visit(')) {
            continue;
        }

        $misplacedBrowserTests[] = $relativePath;
    }

    expect($misplacedBrowserTests)->toBeEmpty();
});

test('direct Livewire component tests remain in the Integration suite', function (): void {
    // Arrange
    $featureLivewireDirectory = base_path('tests/Feature/Livewire');

    // Act
    $misplacedLivewireTests = is_dir($featureLivewireDirectory)
        ? iterator_to_array(
            Finder::create()->files()->in($featureLivewireDirectory)->name('*.php'),
        )
        : [];

    // Assert
    expect($misplacedLivewireTests)->toBeEmpty();
});

test('Feature tests remain aligned to HTTP and architecture boundaries', function (): void {
    // Arrange
    $featureWorkflowsDirectory = base_path('tests/Feature/Workflows');

    // Act
    $misplacedWorkflowTests = is_dir($featureWorkflowsDirectory)
        ? iterator_to_array(
            Finder::create()->files()->in($featureWorkflowsDirectory)->name('*.php'),
        )
        : [];

    // Assert
    expect($misplacedWorkflowTests)->toBeEmpty();
});

test('the obsolete Laravel Dusk configuration is not present', function (): void {
    expect(file_exists(base_path('phpunit.dusk.xml')))->toBeFalse();
});

test('unit tests stay independent of Laravel application services', function (): void {
    // Arrange
    $forbiddenPatterns = [
        'RefreshDatabase::class',
        'app(',
        'livewire(',
        'Validator::',
        'assertDatabase',
        'DB::',
        'route(',
        '::factory()->create(',
    ];
    $violations = [];

    // Act
    foreach (Finder::create()->files()->in(base_path('tests/Unit'))->name('*.php') as $file) {
        $source = $file->getContents();

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($source, $pattern)) {
                $violations[] = sprintf('%s contains %s', $file->getRelativePathname(), $pattern);
            }
        }
    }

    // Assert
    expect($violations)->toBeEmpty();
});

test('application tests use TestDouble instead of Mockery', function (): void {
    // Arrange
    $mockeryUsages = [];

    // Act
    foreach (Finder::create()->files()->in(base_path('tests'))->name('*.php') as $file) {
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());

        if ($relativePath === 'Feature/Architecture/TestSuiteArchitectureTest.php') {
            continue;
        }

        if (preg_match('/mockery/i', $file->getContents()) === 1) {
            $mockeryUsages[] = $relativePath;
        }
    }

    // Assert
    expect($mockeryUsages)->toBeEmpty();
});
