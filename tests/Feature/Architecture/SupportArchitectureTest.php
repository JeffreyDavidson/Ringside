<?php

declare(strict_types=1);

/*
 * App\Support holds technical building blocks that know nothing about this application, so it may not depend on any
 * other top-level application namespace, nor on Filament or Livewire. The list of application namespaces is read from
 * the app directory, so a new top-level folder is covered without editing this file.
 */
$applicationNamespaces = array_values(array_filter(
    array_map(
        static fn (string $directory): string => 'App\\'.basename($directory),
        glob(__DIR__.'/../../../app/*', GLOB_ONLYDIR) ?: [],
    ),
    static fn (string $namespace): bool => $namespace !== 'App\\Support',
));

arch('support code uses nothing from the application, Filament or Livewire')
    ->expect('App\Support')
    ->not->toUse([...$applicationNamespaces, 'Filament', 'Livewire']);
