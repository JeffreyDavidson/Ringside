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
use App\Livewire\Titles\Tables\TitleHistory;
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
    [TitleHistory::class, 'titleId'],
    [PreviousEvents::class, 'venueId'],
    [App\Livewire\Wrestlers\Tables\PreviousManagers::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousMatches::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousStables::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousTagTeams::class, 'wrestlerId'],
    [App\Livewire\Wrestlers\Tables\PreviousTitleChampionships::class, 'wrestlerId'],
]);

/**
 * Container-resolution calls that fetch a collaborator inline. Plain `app()`
 * calls on the application instance such as `app()->environment()`,
 * `app()->getLocale()` or `app()->isProduction()` do not resolve anything and are not matched.
 *
 * @return array<string, string>
 */
function inlineResolutionPatterns(): array
{
    $resolver = 'make|makeWith|get|resolve|call|build';

    return [
        'app(Class::class) / app(\'...\')' => '/(?<![\w>:$])app\s*\(\s*[^\s)]/',
        'resolve(...)' => '/(?<![\w>:$])resolve\s*\(/',
        'App::make(...)' => "/(?<![\\w>$])App::(?:{$resolver})\\s*\\(/",
        'app()->make(...)' => "/(?<![\\w>:$])app\\s*\\(\\s*\\)\\s*->\\s*(?:{$resolver})\\s*\\(/",
        'Container::getInstance()' => '/(?<![\w>$])Container::getInstance\s*\(/',
    ];
}

/**
 * @param  array<string, string>  $allowed  Relative path => reason
 * @return list<string>
 */
function inlineResolutionViolations(string $directory, string $suffix, array $allowed = []): array
{
    $violations = [];

    foreach (Finder::create()->files()->in($directory)->name($suffix) as $file) {
        $relativePath = str_replace(base_path().'/', '', $file->getRealPath());

        if (array_key_exists($relativePath, $allowed)) {
            continue;
        }

        foreach (explode("\n", $file->getContents()) as $index => $line) {
            foreach (inlineResolutionPatterns() as $description => $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $number = $index + 1;
                    $violations[] = "{$relativePath}:{$number} resolves a collaborator inline with {$description}: ".trim($line);
                }
            }
        }
    }

    return $violations;
}

test('Livewire components receive collaborators by injection instead of resolving them inline', function (): void {
    // Documented exceptions: Livewire calls these methods itself, so they cannot take injected parameters.
    $allowed = [
        'app/Livewire/Events/Forms/CreateEditForm.php' => 'rules() is called by Livewire validate(); needs PromotionContextService',
        'app/Livewire/Base/BaseForm.php' => 'rules() is called by Livewire validate(); needs PromotionContextService',
    ];

    // Act
    $violations = inlineResolutionViolations(app_path('Livewire'), '*.php', $allowed);

    // Assert
    expect($violations)->toBeEmpty(implode("\n", $violations));
});

test('the Livewire inline resolution allow-list only names files that still resolve inline', function (): void {
    // Arrange
    $allowed = ['app/Livewire/Events/Forms/CreateEditForm.php', 'app/Livewire/Base/BaseForm.php'];

    // Act
    $stale = array_filter(
        $allowed,
        fn (string $path): bool => inlineResolutionViolations(dirname(base_path($path)), basename($path)) === [],
    );

    // Assert
    expect($stale)->toBeEmpty();
});

test('Blade views never resolve classes from the container', function (): void {
    // Act
    $violations = inlineResolutionViolations(resource_path('views'), '*.blade.php');

    // Assert
    expect($violations)->toBeEmpty(implode("\n", $violations));
});

test('the inline resolution patterns ban resolving calls and allow application helper calls', function (string $line, bool $banned): void {
    // Act
    $matches = array_filter(
        inlineResolutionPatterns(),
        fn (string $pattern): bool => preg_match($pattern, $line) === 1,
    );

    // Assert
    expect($matches !== [])->toBe($banned);
})->with([
    ['$a = app(SomeAction::class);', true],
    ["\$a = app('cache');", true],
    ['$a = resolve(SomeAction::class);', true],
    ['$a = App::make(SomeAction::class);', true],
    ['$a = app()->make(SomeAction::class);', true],
    ['$a = \\Illuminate\\Container\\Container::getInstance();', true],
    ["abort_unless(app()->environment(['local']), 404);", false],
    ['{{ app()->getLocale() }}', false],
    ['$a = app()->isProduction();', false],
    ['$a = $this->resolve($x);', false],
    ['$a = $query->resolve();', false],
]);
