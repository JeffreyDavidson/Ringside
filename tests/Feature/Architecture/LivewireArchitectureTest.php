<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Symfony\Component\Finder\Finder;

test('table configuration hooks are not exposed as Livewire actions', function (): void {
    $publicConfigurationHooks = [];

    foreach (Finder::create()->files()->in(app_path('Livewire'))->name('*.php') as $file) {
        if (! preg_match('/public function configure\s*\(/', $file->getContents())) {
            continue;
        }

        $publicConfigurationHooks[] = $file->getRelativePathname();
    }

    expect($publicConfigurationHooks)->toBeEmpty();
});

test('modal form bindings reference public properties on their form objects', function (): void {
    $checkedBindings = 0;
    $invalidBindings = [];

    foreach (File::glob(resource_path('views/livewire/*/modals/*-modal.blade.php')) as $viewPath) {
        $domain = basename(dirname(dirname($viewPath)));
        $modal = Str::of(basename($viewPath, '.blade.php'))->studly();
        $component = 'App\\Livewire\\'.Str::studly($domain).'\\Modals\\'.$modal;

        if (! class_exists($component)) {
            throw new LogicException("Livewire component {$component} does not exist.");
        }

        $formType = (new ReflectionProperty($component, 'form'))->getType();

        if (! $formType instanceof ReflectionNamedType || ! is_a($formType->getName(), Form::class, true)) {
            throw new LogicException("Livewire component {$component} must declare a typed form object.");
        }

        preg_match_all(
            '/wire:model(?:\.[A-Za-z0-9.-]+)?=["\']form\.([A-Za-z_][A-Za-z0-9_]*)/',
            File::get($viewPath),
            $matches,
        );

        $form = new ReflectionClass($formType->getName());

        foreach (array_unique($matches[1]) as $property) {
            $checkedBindings++;

            if ($form->hasProperty($property) && $form->getProperty($property)->isPublic()) {
                continue;
            }

            $invalidBindings[] = "{$viewPath}: form.{$property}";
        }
    }

    expect($checkedBindings)->toBeGreaterThan(0)
        ->and($invalidBindings)->toBeEmpty();
});

test('component context identifiers are locked', function (string $component, string $property): void {
    if (! class_exists($component)) {
        throw new LogicException("Livewire component {$component} does not exist.");
    }

    $lockedAttributes = new ReflectionProperty($component, $property)->getAttributes(Locked::class);

    expect($lockedAttributes)->toHaveCount(1);
})->with([
    ['App\\Livewire\\Managers\\Tables\\PreviousStables', 'managerId'],
    ['App\\Livewire\\Managers\\Tables\\PreviousTagTeams', 'managerId'],
    ['App\\Livewire\\Managers\\Tables\\PreviousWrestlers', 'managerId'],
    ['App\\Livewire\\Matches\\Modals\\FormModal', 'eventId'],
    ['App\\Livewire\\Matches\\Modals\\ResultModal', 'matchId'],
    ['App\\Livewire\\Matches\\Tables\\MatchesTable', 'eventId'],
    ['App\\Livewire\\Referees\\Tables\\PreviousMatches', 'refereeId'],
    ['App\\Livewire\\Stables\\Tables\\PreviousManagers', 'stableId'],
    ['App\\Livewire\\Stables\\Tables\\PreviousTagTeams', 'stableId'],
    ['App\\Livewire\\Stables\\Tables\\PreviousWrestlers', 'stableId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousManagers', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousMatches', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousStables', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousTitleChampionships', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousWrestlers', 'tagTeamId'],
    ['App\\Livewire\\Titles\\Tables\\PreviousTitleChampionships', 'titleId'],
    ['App\\Livewire\\Venues\\Tables\\PreviousEvents', 'venueId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousManagers', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousMatches', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousStables', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousTagTeams', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousTitleChampionships', 'wrestlerId'],
]);
