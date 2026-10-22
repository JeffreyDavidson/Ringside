<?php

declare(strict_types=1);

/*
 * A ViewModel only builds the data of one page (laravel-conventions rule view-layer.md). Controllers and Livewire components call
 * it; the layers below the view never do, and it never calls an Action or another ViewModel. Queries and Services
 * are covered by QueryArchitectureTest and ServiceArchitectureTest.
 */
arch('actions do not depend on view models')
    ->expect('App\Actions')
    ->not->toUse('App\ViewModels');

arch('lifecycle rules do not depend on view models')
    ->expect('App\Lifecycle')
    ->not->toUse('App\ViewModels');

arch('view models do not run actions')
    ->expect('App\ViewModels')
    ->not->toUse('App\Actions');

// A namespace expectation ignores uses inside the same namespace, so each ViewModel is checked against the others.
$viewModels = array_map(
    fn (string $file): string => str_replace('/', '\\', substr($file, strlen(__DIR__.'/../../../app/ViewModels/'), -4)),
    glob(__DIR__.'/../../../app/ViewModels/{,*/}*.php', GLOB_BRACE) ?: [],
);

// ViewModels live in a domain folder (laravel-conventions rule view-layer.md), never directly in app/ViewModels.
test('every view model sits in a domain folder', function () use ($viewModels): void {
    expect(array_filter($viewModels, fn (string $viewModel): bool => ! str_contains($viewModel, '\\')))->toBeEmpty();
});

foreach ($viewModels as $viewModel) {
    arch("{$viewModel} does not depend on another view model")
        ->expect("App\\ViewModels\\{$viewModel}")
        ->not->toUse(array_map(fn (string $other): string => "App\\ViewModels\\{$other}", array_values(array_diff($viewModels, [$viewModel]))));
}
