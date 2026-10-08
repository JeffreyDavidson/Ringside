<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Translation\Translator;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

use function Pest\Laravel\freezeTime;
use function Pest\Laravel\withoutVite;

pest()->tia()->baselined();

pest()->tia()->watch([
    'phpunit.xml' => 'tests',
]);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Browser');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        withoutVite();
        freezeTime();
        reverseUnorderedSelectsWhenRequested();
    })
    ->in(
        'Integration/Actions',
        'Integration/Builders',
        'Integration/Casts',
        'Integration/Collections',
        'Integration/Concurrency',
        'Integration/Config',
        'Integration/Database',
        'Integration/Enums',
        'Integration/Exceptions',
        'Integration/Lifecycle',
        'Integration/Livewire',
        'Integration/Mail',
        'Integration/Models',
        'Integration/Policies',
        'Integration/Promotions',
        'Integration/Providers',
        'Integration/Queries',
        'Integration/Rules',
        'Integration/Services',
        'Integration/Support',
        'Integration/Validation',
        'Integration/View',
        'Integration/ViewModels',
        'Integration/Workflows',
    );

/*
 * Integration/DatabaseMigrations is deliberately missing from the list above. A new Integration directory must be
 * added to that list, or its tests have no application.
 *
 * Tests that change the schema (DDL) or run migrations cannot use RefreshDatabase: MySQL commits the test
 * transaction on DDL, so their schema changes and data would leak into later tests. They rebuild the schema with
 * migrate:fresh before each test, and again afterwards so the next test starts from the full schema. An in-memory
 * SQLite database is discarded with the test, so it needs no second rebuild.
 */
pest()
    ->extend(TestCase::class)
    ->beforeEach(function () {
        withoutVite();
        freezeTime();
        reverseUnorderedSelectsWhenRequested();
        rebuildDatabaseSchema();
    })
    ->afterEach(function () {
        if (! databaseIsInMemory()) {
            rebuildDatabaseSchema();
        }
    })
    ->in('Integration/DatabaseMigrations');

pest()
    ->beforeEach(function () {
        Relation::requireMorphMap(false);
    })
    ->afterEach(function () {
        Relation::requireMorphMap();
    })
    ->in('Integration/Models/Concerns');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        withoutVite();
        freezeTime();
        reverseUnorderedSelectsWhenRequested();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function administrator(): User
{
    return User::factory()->administrator()->create(['status' => UserStatus::Active]);
}

function basicUser(): User
{
    return User::factory()->basicUser()->create(['status' => UserStatus::Active]);
}

/**
 * Adapt a test observer to Laravel's validation failure callback contract.
 */
function validationFailureCallback(Closure $observer): Closure
{
    return function (string $message) use ($observer): PotentiallyTranslatedString {
        $observer($message);

        return new PotentiallyTranslatedString($message, app(Translator::class));
    };
}

/**
 * Opt-in guard against tests that depend on the order of rows a query never ordered: with REVERSE_UNORDERED_SELECTS=1
 * SQLite returns every unordered result in reverse, so such a test fails instead of passing by accident. See
 * docs/workflows/ci-cd.md.
 */
function reverseUnorderedSelectsWhenRequested(): void
{
    if (getenv('REVERSE_UNORDERED_SELECTS') !== '1' || ! runsOnDriver('sqlite')) {
        return;
    }

    DB::statement('PRAGMA reverse_unordered_selects = ON');
}

/**
 * Return a date that must exist for the tested state.
 */
function requiredDate(?Carbon $date): Carbon
{
    return $date ?? throw new RuntimeException('Expected the model date to be present.');
}

/**
 * Return a model that must exist for the tested state.
 *
 * @template TModel of Model
 *
 * @param  TModel|null  $model
 * @return TModel
 */
function requiredModel(?Model $model): Model
{
    return $model ?? throw new RuntimeException('Expected the model relationship to be present.');
}

/**
 * Reload a separate model instance that must still exist.
 *
 * @template TModel of Model
 *
 * @param  TModel|null  $model
 * @return TModel
 */
function freshModel(?Model $model): Model
{
    if ($model === null) {
        throw new RuntimeException('Expected the model to be present before reloading it.');
    }

    $freshModel = $model->fresh();
    if ($freshModel === null) {
        throw new RuntimeException('Expected the model to exist when reloading it.');
    }

    return $freshModel;
}

/**
 * Wait until a JavaScript condition holds in the browser (at most five seconds), then assert it.
 *
 * Use this instead of fixed sleeps so a browser test waits exactly as long as the page needs.
 */
function waitForScript(AwaitableWebpage|PendingAwaitablePage $page, string $condition): void
{
    $page->assertScript(<<<JS
        () => new Promise((resolve) => {
            const deadline = Date.now() + 5000;
            const holds = () => {
                try {
                    return Boolean({$condition});
                } catch {
                    return false;
                }
            };
            const check = () => {
                if (holds()) {
                    resolve(true);
                } else if (Date.now() > deadline) {
                    resolve(false);
                } else {
                    setTimeout(check, 20);
                }
            };
            check();
        })
        JS);
}

/**
 * Wait until a JavaScript condition holds and the page has stopped animating (at most five seconds), then assert it.
 *
 * Alpine and Livewire flip state before the CSS transition for it exists (it starts a frame later), so a single
 * "no animations" reading can be taken too early. The page must stay free of animations for 120 ms in a row.
 */
function waitForSettledScript(AwaitableWebpage|PendingAwaitablePage $page, string $condition = 'true'): void
{
    $page->assertScript(<<<JS
        () => new Promise((resolve) => {
            const deadline = Date.now() + 5000;
            let quietPolls = 0;
            const holds = () => {
                try {
                    return Boolean({$condition});
                } catch {
                    return false;
                }
            };
            const check = () => {
                quietPolls = holds() && document.getAnimations().length === 0 ? quietPolls + 1 : 0;
                if (quietPolls >= 6) {
                    resolve(true);
                } else if (Date.now() > deadline) {
                    resolve(false);
                } else {
                    setTimeout(check, 20);
                }
            };
            check();
        })
        JS);
}

/**
 * Wait until every CSS animation and transition on the page has finished.
 *
 * Use this before clicking a toggle that a still-running animation could re-render, and instead of fixed sleeps that
 * guess at a transition's duration.
 */
function waitForAnimationsToSettle(AwaitableWebpage|PendingAwaitablePage $page): void
{
    waitForSettledScript($page);
}

/**
 * Resize the viewport, then wait for the layout animations the new size triggers to finish.
 *
 * The browser plugin retries an action in one second attempts, so a toggle clicked right after a resize can land
 * twice (open, then closed again) when the first attempt is slow.
 */
function resizeAndSettle(AwaitableWebpage|PendingAwaitablePage $page, int $width, int $height): void
{
    $page->resize($width, $height);
    waitForAnimationsToSettle($page);
}

/**
 * Wait until the modal is hidden (or absent) and has finished animating out.
 */
function waitForModalToClose(AwaitableWebpage|PendingAwaitablePage $page): void
{
    waitForSettledScript($page, '! document.getElementById("modal-container")?.checkVisibility()');
}

/**
 * Wait until the opened modal is shown and has finished animating in.
 *
 * Livewire renders the form into the modal while it is still hidden, so value assertions can pass before it shows.
 * Typing does not wait for visibility, so click the field you type into (a click waits until it is actionable and
 * focuses it) instead of relying on the modal's focus trap, which skips controls that are still hidden when it activates.
 */
function waitForModalReady(AwaitableWebpage|PendingAwaitablePage $page): void
{
    waitForSettledScript($page, 'document.getElementById("modal-container")?.checkVisibility()');
}
