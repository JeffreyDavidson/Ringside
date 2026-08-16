<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

test('application runtime code does not create records through model factories', function () {
    $factoryUsages = [];

    foreach (Finder::create()->files()->in(app_path())->name('*.php') as $file) {
        if (! str_contains($file->getContents(), '::factory(')) {
            continue;
        }

        $factoryUsages[] = $file->getRelativePathname();
    }

    expect($factoryUsages)->toBeEmpty();
});

test('Livewire components do not instantiate other Livewire components', function () {
    $componentConstructions = [];

    foreach (Finder::create()->files()->in(app_path('Livewire'))->name('*.php') as $file) {
        if (! preg_match('/new\s+Actions\s*\(/', $file->getContents())) {
            continue;
        }

        $componentConstructions[] = $file->getRelativePathname();
    }

    expect($componentConstructions)->toBeEmpty();
});
