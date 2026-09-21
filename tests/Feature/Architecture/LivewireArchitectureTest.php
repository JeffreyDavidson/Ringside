<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousStables;
use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Livewire\Matches\Modals\FormModal;
use App\Livewire\Matches\Modals\ResultModal;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Livewire\Referees\Tables\PreviousMatches;
use App\Livewire\Stables\Tables\PreviousManagers;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Livewire\Venues\Tables\PreviousEvents;
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
        $domain = basename(dirname($viewPath, 2));
        $modal = Str::of(basename($viewPath, '.blade.php'))->studly();
        $component = 'App\\Livewire\\'.Str::studly($domain).'\\Modals\\'.$modal;

        if (! class_exists($component)) {
            throw new LogicException("Livewire component {$component} does not exist.");
        }

        $formType = new ReflectionProperty($component, 'form')->getType();

        if (! $formType instanceof ReflectionNamedType || ! is_a($formType->getName(), Form::class, true)) {
            throw new LogicException("Livewire component {$component} must declare a typed form object.");
        }

        preg_match_all('/wire:model(?:\.[A-Za-z0-9.-]+)?=["\']([^"\']+)["\']/', File::get($viewPath), $matches);

        $form = new ReflectionClass($formType->getName());

        foreach (array_unique($matches[1]) as $binding) {
            $checkedBindings++;
            $property = Str::of($binding)->after('form.')->before('.')->value();

            if (! str_starts_with($binding, 'form.')) {
                $invalidBindings[] = "{$viewPath}: {$binding}";

                continue;
            }

            if ($form->hasProperty($property) && $form->getProperty($property)->isPublic()) {
                continue;
            }

            $invalidBindings[] = "{$viewPath}: form.{$property}";
        }

        if ($matches[1] === []) {
            $invalidBindings[] = "{$viewPath}: no form bindings found";
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
    [PreviousStables::class, 'managerId'],
    [PreviousTagTeams::class, 'managerId'],
    [PreviousWrestlers::class, 'managerId'],
    [FormModal::class, 'eventId'],
    [ResultModal::class, 'matchId'],
    [MatchesTable::class, 'eventId'],
    [PreviousMatches::class, 'refereeId'],
    [PreviousManagers::class, 'stableId'],
    [App\Livewire\Stables\Tables\PreviousTagTeams::class, 'stableId'],
    [App\Livewire\Stables\Tables\PreviousWrestlers::class, 'stableId'],
    [App\Livewire\TagTeams\Tables\PreviousManagers::class, 'tagTeamId'],
    [App\Livewire\TagTeams\Tables\PreviousMatches::class, 'tagTeamId'],
    [App\Livewire\TagTeams\Tables\PreviousStables::class, 'tagTeamId'],
    [PreviousTitleChampionships::class, 'tagTeamId'],
    [App\Livewire\TagTeams\Tables\PreviousWrestlers::class, 'tagTeamId'],
    [App\Livewire\Titles\Tables\PreviousTitleChampionships::class, 'titleId'],
    [PreviousEvents::class, 'venueId'],
    [App\Livewire\Wrestlers\Tables\PreviousManagers::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousMatches::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousStables::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousTagTeams::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousTitleChampionships::class, 'wrestlerId'],
]);
